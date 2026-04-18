<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Entity\CompoundIdentity;
use TinyBlocks\BuildingBlocks\Entity\CompoundIdentityBehavior;

final readonly class AppointmentId implements CompoundIdentity
{
    use CompoundIdentityBehavior;

    public function __construct(public string $tenantId, public string $appointmentId)
    {
    }
}
