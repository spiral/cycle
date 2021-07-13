<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Hydrator;

use ReflectionClass;
use ReflectionProperty;

class ClassPropertiesExtractor
{
    /**
     * Extract all properties from given class
     */
    public function extract(object $class): array
    {
        $hiddenProperties = [];
        $visibleProperties = [];

        $originalClass = new ReflectionClass($class);

        $properties = $this->findAllInstanceProperties($originalClass);
        foreach ($properties as $property) {
            $className = $property->getDeclaringClass()->getName();

            if ($property->isPrivate() || $property->isProtected()) {
                $hiddenProperties[$className][$property->getName()] = $property->getName();
            } else {
                $visibleProperties[$property->getName()] = $property->getName();
            }
        }

        return [
            'hidden' => $hiddenProperties,
            'visible' => $visibleProperties
        ];
    }

    /**
     * Find all class properties recursively using class hierarchy without
     * removing name redefinitions
     *
     * @return ReflectionProperty[]
     */
    private function findAllInstanceProperties(?ReflectionClass $class = null): array
    {
        if (!$class) {
            return [];
        }

        return array_merge(
            $this->findAllInstanceProperties($class->getParentClass() ?: null), // of course PHP is shit.
            array_filter(
                $class->getProperties(),
                static function (ReflectionProperty $property): bool {
                    return !$property->isStatic();
                }
            )
        );
    }
}