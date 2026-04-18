<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;

final readonly class OrderShipped implements DomainEvent
{
    public function __construct(public string $carrier)
    {
    }
}
