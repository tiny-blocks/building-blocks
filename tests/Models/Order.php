<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use InvalidArgumentException;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Utc;

final class Order implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $status = 'draft';

    private function __construct(private readonly OrderId $id)
    {
    }

    public static function place(string $item, OrderId $orderId): Order
    {
        $order = new Order(id: $orderId);
        $order->status = 'placed';
        $order->pushEvent(event: new OrderPlaced(item: $item));

        return $order;
    }

    public static function reconstitutePartial(
        Identity $identity,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): static {
        if (!$identity instanceof OrderId) {
            $template = 'Expected identity of type <%s>, got <%s>.';

            throw new InvalidArgumentException(message: sprintf($template, OrderId::class, $identity::class));
        }

        $order = new Order(id: $identity);
        $order->aggregateVersion = $aggregateVersion;
        $order->recordedEvents = EventRecords::createFromEmpty();

        return $order;
    }

    public function ship(string $carrier, ?Utc $shippedAt = null): void
    {
        $this->status = 'shipped';
        $this->pushEvent(event: new OrderShipped(carrier: $carrier), occurredAt: $shippedAt);
    }
}
