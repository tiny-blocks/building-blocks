<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

trait DomainEventBehavior
{
    public function revision(): Revision
    {
        return Revision::initial();
    }
}
