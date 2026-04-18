<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidRevision;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Revision implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidRevision(value: $value);
        }
    }

    public static function initial(): Revision
    {
        return new Revision(value: 1);
    }

    public static function of(int $value): Revision
    {
        return new Revision(value: $value);
    }
}
