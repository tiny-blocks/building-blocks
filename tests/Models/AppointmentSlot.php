<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Entity\CompoundIdentity;
use TinyBlocks\BuildingBlocks\Entity\CompoundIdentityBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final readonly class AppointmentSlot implements CompoundIdentity
{
    use CompoundIdentityBehavior;

    public function __construct(public string $tenantId, public int $practitionerId, public Revision $revision)
    {
    }
}
