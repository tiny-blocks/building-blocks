<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;

final class Order implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $status = 'draft';

    private function __construct(private OrderId $id)
    {
    }

    public static function reconstitute(OrderId $orderId, SequenceNumber $sequenceNumber): Order
    {
        $order = new Order(id: $orderId);
        $order->reconstituteSequenceNumber(sequenceNumber: $sequenceNumber);
        return $order;
    }

    public static function place(OrderId $orderId, string $item): Order
    {
        $order = new Order(id: $orderId);
        $order->status = 'placed';
        $order->push(event: new OrderPlaced(item: $item));

        return $order;
    }

    public function ship(string $carrier): void
    {
        $this->status = 'shipped';
        $this->push(event: new OrderShipped(carrier: $carrier));
    }
}
