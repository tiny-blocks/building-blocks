<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal;

use ReflectionClass;
use ReflectionProperty;

final class AggregateReflection
{
    private function __construct()
    {
    }

    /**
     * Creates an instance of the given class without invoking its constructor.
     *
     * @template T of object
     * @param class-string<T> $class The fully qualified class name to instantiate.
     * @return T The instance created without running the constructor.
     */
    public static function instantiate(string $class): object
    {
        return new ReflectionClass(objectOrClass: $class)->newInstanceWithoutConstructor();
    }

    public static function assignProperty(object $target, string $property, mixed $value): void
    {
        new ReflectionProperty(class: $target, property: $property)->setValue($target, $value);
    }

    /**
     * Assigns each state entry to its matching property on the target.
     *
     * @param array<string, mixed> $state The property-keyed state to assign.
     */
    public static function hydrate(object $target, array $state): void
    {
        foreach ($state as $property => $value) {
            if (property_exists($target, $property)) {
                AggregateReflection::assignProperty(target: $target, property: $property, value: $value);
            }
        }
    }

    /**
     * Returns the target's required properties that remain uninitialized.
     *
     * @return list<string> The names of the uninitialized properties.
     */
    public static function uninitializedRequiredProperties(object $target): array
    {
        $missing = [];

        foreach (new ReflectionClass(objectOrClass: $target)->getProperties() as $property) {
            if (!$property->isInitialized($target)) {
                $missing[] = $property->getName();
            }
        }

        return $missing;
    }
}
