<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidModelVersion;
use TinyBlocks\BuildingBlocks\Ordinal;
use TinyBlocks\BuildingBlocks\OrdinalBehavior;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class ModelVersion implements ValueObject, Ordinal
{
    use ValueObjectBehavior;
    use OrdinalBehavior;

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

    public function value(): int
    {
        return $this->value;
    }
}
