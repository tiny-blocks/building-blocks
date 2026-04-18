<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\Vo\ValueObjectBehavior;

trait SingleIdentityBehavior
{
    use ValueObjectBehavior;

    public function getIdentityValue(): mixed
    {
        $properties = get_object_vars($this);

        return reset($properties);
    }
}
