<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventBehavior;

final readonly class PaymentConfirmed implements IntegrationEvent
{
    use IntegrationEventBehavior;

    public function __construct(public string $orderId)
    {
    }
}
