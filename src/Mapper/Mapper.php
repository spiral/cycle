<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Mapper\Proxy\ProxyEntityFactory;
use Cycle\ORM\RelationMap;
use Cycle\ORM\Schema;

/**
 * Provide the ability to carry data over the specific class instances using proxy classes.
 *
 * Supports table inheritance using hidden entity field.
 */
class Mapper extends DatabaseMapper
{
    // system column to store entity type for STI
    public const ENTITY_TYPE = '_type';

    protected string $entity;

    protected array $children = [];

    protected ProxyEntityFactory $entityFactory;

    private RelationMap $relationMap;

    public function __construct(ORMInterface $orm, ProxyEntityFactory $entityFactory, string $role)
    {
        parent::__construct($orm, $role);

        $this->entity = $orm->getSchema()->define($role, Schema::ENTITY);
        $this->children = $orm->getSchema()->define($role, Schema::CHILDREN) ?? [];
        $this->entityFactory = $entityFactory;
        $this->relationMap = $orm->getRelationMap($role);
    }

    public function init(array $data): object
    {
        $class = $this->resolveClass($data);
        return $this->entityFactory->create($this->orm, $class, $data, $class);
    }

    public function hydrate(object $entity, array $data): object
    {
        $this->entityFactory->upgrade($this->orm, $this->role, $entity, $data);
        return $entity;
    }

    public function extract(object $entity): array
    {
        return $this->entityFactory->extractData($this->relationMap, $entity)
            + $this->entityFactory->extractRelations($this->relationMap, $entity);
    }

    public function fetchFields(object $entity): array
    {
        $columns = array_intersect_key(
            $this->entityFactory->extractData($this->relationMap, $entity),
            array_flip($this->fields)
        );

        $class = get_class($entity);
        if ($class !== $this->entity) {
            // inheritance
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                    break;
                }
            }
        }

        return $columns;
    }

    public function fetchRelations(object $entity): array
    {
        return $this->entityFactory->extractRelations($this->relationMap, $entity);
    }

    /**
     * Classname to represent entity.
     */
    protected function resolveClass(array $data): string
    {
        $class = $this->entity;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }
}
