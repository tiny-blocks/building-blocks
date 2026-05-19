<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;

final readonly class ReservationConfirmed implements DomainEvent
{
    use DomainEventBehavior;
}
