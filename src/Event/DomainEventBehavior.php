<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Utc;

trait DomainEventBehavior
{
    public function revision(): Revision
    {
        return Revision::initial();
    }

    public function occurredAt(): ?Utc
    {
        return null;
    }
}
