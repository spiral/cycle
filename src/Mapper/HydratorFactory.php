<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException;
use CodeGenerationUtils\Visitor\ClassRenamerVisitor;
use Cycle\ORM\Mapper\Hydrator\Configuration;
use GeneratedHydrator\GeneratedHydrator;
use GeneratedHydratorTestAsset\HydratedObject;
use Laminas\Hydrator\HydratorInterface;
use PhpParser\NodeTraverser;
use ReflectionClass;

class HydratorFactory
{
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;

        if ($config->isUsedFileWriteStrategy()) {
            // Autoload generated hydrators inside target dir
            spl_autoload_register($this->config->getGeneratedClassAutoloader());
        }
    }

    /**
     * Create a new hydrator for given entity class
     */
    public function create(string $class): HydratorInterface
    {
        $this->config->setHydratedClassName($class);
        $hydratorClass = $this->getHydratorClass();

        return new $hydratorClass();
    }

    /**
     * Retrieves the generated hydrator FQCN
     *
     * @throws InvalidGeneratedClassesDirectoryException
     * @psalm-return class-string<GeneratedHydrator<HydratedObject>>
     */
    private function getHydratorClass(): string
    {
        $inflector = $this->config->getClassNameInflector();
        /** @psalm-var class-string $realClassName */
        $realClassName = $inflector->getUserClassName($this->config->getHydratedClassName());

        /** @psalm-var class-string<GeneratedHydrator<HydratedObject>> $hydratorClassName */
        $hydratorClassName = $inflector->getGeneratedClassName($realClassName, ['factory' => static::class]);

        if (!class_exists($hydratorClassName) && $this->config->doesAutoGenerateProxies()) {
            $generator = $this->config->getHydratorGenerator();
            $originalClass = new ReflectionClass($realClassName);
            $generatedAst = $generator->generate($originalClass);
            $traverser = new NodeTraverser();

            $traverser->addVisitor(new ClassRenamerVisitor($originalClass, $hydratorClassName));

            $this->config->getGeneratorStrategy()->generate($traverser->traverse($generatedAst));
            $this->config->getGeneratedClassAutoloader()->__invoke($hydratorClassName);
        }

        return $hydratorClassName;
    }
}