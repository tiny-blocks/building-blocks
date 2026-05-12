<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\Vo\ValueObjectBehavior;

trait CompoundIdentityBehavior
{
    use ValueObjectBehavior;

    public function identityValue(): mixed
    {
        return get_object_vars($this);
    }
}
