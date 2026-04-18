<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final class Order implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private const string IDENTITY = 'orderId';

    private string $status = 'draft';

    private function __construct(private OrderId $orderId)
    {
    }

    public static function place(OrderId $orderId, string $item): Order
    {
        $order = new Order(orderId: $orderId);
        $order->status = 'placed';
        $order->pushEvent(event: new OrderPlaced(item: $item), revision: new Revision(value: 1));

        return $order;
    }

    public function ship(string $carrier): void
    {
        $this->status = 'shipped';
        $this->pushEvent(event: new OrderShipped(carrier: $carrier), revision: new Revision(value: 1));
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
