<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy;

use Closure;
use Cycle\ORM\Mapper\HydratorFactory;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RelationMap;
use Doctrine\Instantiator\Instantiator;

class ProxyEntityFactory
{
    /**
     * @var string[]
     * @psalm-var class-string
     */
    private array $classMap = [];
    private array $classScope = [];
    private Instantiator $instantiator;
    private Closure $initializer;
    private HydratorFactory $hydratorFactory;
    private array $hydratorCache = [];

    public function __construct(HydratorFactory $hydratorFactory, Instantiator $instantiator)
    {
        $this->instantiator = $instantiator;
        $this->initializer = static function (object $self, array $properties): void {
            foreach ($properties as $name) {
                unset($self->$name);
            }
        };

        $this->hydratorFactory = $hydratorFactory;
    }

    /**
     * Create empty entity
     */
    public function create(
        ORMInterface $orm,
        string $role,
        array $data,
        string $sourceClass
    ): object
    {
        $relMap = $orm->getRelationMap($role);
        $class = array_key_exists($sourceClass, $this->classMap)
            ? $this->classMap[$sourceClass]
            : $this->defineClass($relMap, $sourceClass);
        if ($class === null) {
            return (object)$data;
        }

        $proxy = $this->instantiator->instantiate($class);
        $proxy->__cycle_orm_rel_map = $relMap;
        foreach ($this->classScope[$sourceClass] as $scope => $properties) {
            Closure::bind($this->initializer, null, $scope)($proxy, $properties);
        }

        return $proxy;
    }

    public function upgrade(
        ORMInterface $orm,
        string $role,
        object $entity,
        array $data
    ): object
    {
        $class = get_class($entity);

        $hydrator = $this->hydratorCache[$class] ??= $this->hydratorFactory->create($class);

        // new set of data and relations always overwrite entity state
        return $hydrator->hydrate($data, $entity);
    }

    public function extractRelations(RelationMap $relMap, object $entity): array
    {
        if (!property_exists($entity, '__cycle_orm_rel_data')) {
            return array_intersect_key($this->entityToArray($entity), $relMap->getRelations());
        }
        $currentData = $entity->__cycle_orm_rel_data;
        foreach ($relMap->getRelations() as $key => $relation) {
            if (!array_key_exists($key, $currentData)) {
                $arrayData ??= $this->entityToArray($entity);
                $currentData[$key] = $arrayData[$key];
            }
        }
        return $currentData;
    }

    public function extractData(RelationMap $relMap, object $entity): array
    {
        return array_diff_key($this->entityToArray($entity), $relMap->getRelations());
    }

    private function entityToArray(object $entity): array
    {
        $result = [];
        foreach ((array)$entity as $key => $value) {
            $result[$key[0] === "\0" ? substr($key, strrpos($key, "\0", 1) + 1) : $key] = $value;
        }
        unset($result['__cycle_orm_rel_map']);
        unset($result['__cycle_orm_rel_data']);
        return $result;
    }

    private function defineClass(RelationMap $relMap, string $class): ?string
    {
        if (!class_exists($class, true)) {
            $this->classMap[$class] = null;
            $this->classScope[$class] = $this->getScope(null, $relMap);
            return null;
        }
        if (array_key_exists($class, $this->classMap)) {
            return $this->classMap[$class];
        }
        $reflection = new \ReflectionClass($class);
        if ($reflection->isFinal()) {
            throw new \RuntimeException(sprintf('The entity `%s` class is final and can\'t be extended.', $class));
        }
        $className = "{$class} Cycle ORM Proxy";
        // $className = "PROXY_" . md5($class);
        $this->classMap[$class] = $className;
        $this->classScope[$class] = $this->getScope($class, $relMap);
        // Todo Interface
        if (!class_exists($className, false)) {
            if (strpos($className, '\\') !== false) {
                $pos = strrpos($className, '\\');
                $namespaceStr = sprintf("namespace %s;\n", substr($className, 0, $pos));
                $classNameStr = substr($className, $pos + 1);
            } else {
                $namespaceStr = '';
                $classNameStr = $className;
            }

            /** @see \Cycle\ORM\Mapper\Proxy\EntityProxyTrait */
            $classStr = <<<PHP
                {$namespaceStr}
                class {$classNameStr} extends \\{$class} {
                    use \\Cycle\ORM\\Mapper\\Proxy\\EntityProxyTrait;
                }
                PHP;
            eval($classStr);
        }

        return $className;
    }

    private function getScope(?string $class, RelationMap $relMap): array
    {
        if ($class === null) {
            return array_keys($relMap->getRelations());
        }
        // todo reflection
        return [$class => array_keys($relMap->getRelations())];
    }
}
