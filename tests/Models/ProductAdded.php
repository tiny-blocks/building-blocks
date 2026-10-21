<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
use TinyBlocks\BuildingBlocks\Utc;

final readonly class ProductAdded implements DomainEvent
{
    use DomainEventBehavior;

    public function __construct(public string $productId, public ?Utc $addedAt = null)
    {
    }

    public function eventType(): string
    {
        return 'ProductAdded';
    }

    public function occurredAt(): ?Utc
    {
        return $this->addedAt;
    }
}
