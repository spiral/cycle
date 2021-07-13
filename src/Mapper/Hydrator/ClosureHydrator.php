<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Hydrator;

use Closure;

class ClosureHydrator
{
    /**
     * @param array $properties Array of class properties
     * @param object $object
     * @param array $data
     * @return object
     */
    public function hydrate(array $properties, object $object, array $data): object
    {
        foreach ($properties['visible'] as $property) {
            if (!isset($data[$property])) {
                continue;
            }

            $object->{$property} = $data[$property];
        }

        foreach ($properties['hidden'] as $class => $properties) {
            Closure::bind(function (object $object, array $properties, array $data) {
                foreach ($properties as $property) {
                    if (!isset($data[$property])) {
                        continue;
                    }

                    $object->{$property} = $data[$property];
                }
            }, $object, $class)($object, $properties, $data);
        }

        return $object;
    }
}