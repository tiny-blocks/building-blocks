<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;

final readonly class OrderShipped implements DomainEvent
{
    use DomainEventBehavior;

    public function __construct(public string $carrier)
    {
    }

    public function eventType(): string
    {
        return 'OrderShipped';
    }
}
