<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;

final readonly class ReservationBooked implements DomainEvent
{
    use DomainEventBehavior;

    public function eventType(): string
    {
        return 'ReservationBooked';
    }
}
