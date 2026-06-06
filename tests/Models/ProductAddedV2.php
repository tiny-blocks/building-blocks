<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final readonly class ProductAddedV2 implements DomainEvent
{
    use DomainEventBehavior;

    public function __construct(public string $productId, public int $quantity)
    {
    }

    public function revision(): Revision
    {
        return Revision::of(value: 2);
    }

    public function eventType(): string
    {
        return 'ProductAddedV2';
    }
}
