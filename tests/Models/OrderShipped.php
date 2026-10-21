<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
use TinyBlocks\BuildingBlocks\Utc;

final readonly class OrderShipped implements DomainEvent
{
    use DomainEventBehavior;

    public function __construct(public string $carrier, public ?Utc $shippedAt = null)
    {
    }

    public function eventType(): string
    {
        return 'OrderShipped';
    }

    public function occurredAt(): ?Utc
    {
        return $this->shippedAt;
    }
}
