<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Entity\SingleIdentity;
use TinyBlocks\BuildingBlocks\Entity\SingleIdentityBehavior;

final readonly class ReservationId implements SingleIdentity
{
    use SingleIdentityBehavior;

    public function __construct(public string $value)
    {
    }
}
