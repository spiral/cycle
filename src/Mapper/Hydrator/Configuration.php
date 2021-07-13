<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Hydrator;

use CodeGenerationUtils\Exception\InvalidGeneratedClassesDirectoryException;
use CodeGenerationUtils\GeneratorStrategy\EvaluatingGeneratorStrategy;
use CodeGenerationUtils\GeneratorStrategy\FileWriterGeneratorStrategy;
use CodeGenerationUtils\GeneratorStrategy\GeneratorStrategyInterface;

class Configuration extends \GeneratedHydrator\Configuration
{
    public function __construct()
    {
    }

    /**
     * @psalm-param class-string<HydratedClass> $hydratedClassName
     */
    public function setHydratedClassName(string $hydratedClassName): void
    {
        $this->hydratedClassName = $hydratedClassName;
    }

    /**
     * @throws InvalidGeneratedClassesDirectoryException
     */
    public function getGeneratorStrategy(): GeneratorStrategyInterface
    {
        if ($this->generatorStrategy === null) {
            $this->generatorStrategy = new EvaluatingGeneratorStrategy();
        }

        return $this->generatorStrategy;
    }

    /**
     * Check if hydrator will store to a disk after generating
     */
    public function isUsedFileWriteStrategy(): bool
    {
        return $this->generatorStrategy instanceof FileWriterGeneratorStrategy;
    }

}