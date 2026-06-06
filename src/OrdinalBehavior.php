<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks;

trait OrdinalBehavior
{
    abstract public function value(): int;

    public function isAfter(Ordinal $other): bool
    {
        return $this->value() > $other->value();
    }

    public function isBefore(Ordinal $other): bool
    {
        return $this->value() < $other->value();
    }
}
