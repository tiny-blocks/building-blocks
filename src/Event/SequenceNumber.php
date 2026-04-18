<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidSequenceNumber;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class SequenceNumber implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidSequenceNumber(value: $value);
        }
    }

    public static function initial(): SequenceNumber
    {
        return new SequenceNumber(value: 0);
    }

    public static function first(): SequenceNumber
    {
        return new SequenceNumber(value: 1);
    }

    public static function of(int $value): SequenceNumber
    {
        return new SequenceNumber(value: $value);
    }

    public function next(): SequenceNumber
    {
        return new SequenceNumber(value: $this->value + 1);
    }

    public function isAfter(SequenceNumber $other): bool
    {
        return $this->value > $other->value;
    }
}
