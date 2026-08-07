<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidAggregateVersion;
use TinyBlocks\BuildingBlocks\Ordinal;
use TinyBlocks\BuildingBlocks\OrdinalBehavior;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class AggregateVersion implements ValueObject, Ordinal
{
    use ValueObjectBehavior;
    use OrdinalBehavior;

    private function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidAggregateVersion(value: $value);
        }
    }

    /**
     * Creates an AggregateVersion with the initial value of 0.
     *
     * @return AggregateVersion The initial aggregate version.
     */
    public static function initial(): AggregateVersion
    {
        return new AggregateVersion(value: 0);
    }

    /**
     * Creates an AggregateVersion with the first emitted value of 1.
     *
     * @return AggregateVersion The first aggregate version.
     */
    public static function first(): AggregateVersion
    {
        return new AggregateVersion(value: 1);
    }

    /**
     * Creates an AggregateVersion from the given integer value.
     *
     * @param int $value The aggregate version. Must be greater than or equal to 0.
     * @return AggregateVersion The created instance.
     * @throws InvalidAggregateVersion If the value is less than 0.
     */
    public static function of(int $value): AggregateVersion
    {
        return new AggregateVersion(value: $value);
    }

    /**
     * Returns a copy of the AggregateVersion advanced by one.
     *
     * @return AggregateVersion A new instance carrying the next value.
     */
    public function next(): AggregateVersion
    {
        return new AggregateVersion(value: ($this->value + 1));
    }

    public function value(): int
    {
        return $this->value;
    }
}
