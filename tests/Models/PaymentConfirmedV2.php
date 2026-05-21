<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final readonly class PaymentConfirmedV2 implements IntegrationEvent
{
    use IntegrationEventBehavior;

    public function __construct(public string $orderId)
    {
    }

    public function revision(): Revision
    {
        return Revision::of(value: 2);
    }
}
