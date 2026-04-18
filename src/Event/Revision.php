<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidRevision;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Revision implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidRevision(value: $value);
        }
    }
}
