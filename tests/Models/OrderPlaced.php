<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(public string $item)
    {
    }
}
