<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidModelVersion;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class ModelVersion implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidModelVersion(value: $value);
        }
    }

    /**
     * Creates a ModelVersion from the given integer value.
     *
     * @param int $value The schema version. Must be greater than or equal to 0.
     * @return ModelVersion The created instance.
     * @throws InvalidModelVersion If the value is less than 0.
     */
    public static function of(int $value): ModelVersion
    {
        return new ModelVersion(value: $value);
    }

    /**
     * Creates a ModelVersion with the initial value of 0.
     *
     * @return ModelVersion The initial model version.
     */
    public static function initial(): ModelVersion
    {
        return new ModelVersion(value: 0);
    }

    /**
     * Tells whether this model version is strictly after the given one.
     *
     * @param ModelVersion $other The model version to compare against.
     * @return bool True when this value is greater than the other's.
     */
    public function isAfter(ModelVersion $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Tells whether this model version is strictly before the given one.
     *
     * @param ModelVersion $other The model version to compare against.
     * @return bool True when this value is less than the other's.
     */
    public function isBefore(ModelVersion $other): bool
    {
        return $this->value < $other->value;
    }
}
