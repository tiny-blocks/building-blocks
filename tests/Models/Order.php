<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final class Order implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $status = 'draft';

    private function __construct(private OrderId $orderId)
    {
    }

    public static function place(OrderId $orderId, string $item): Order
    {
        $order = new Order(orderId: $orderId);
        $order->status = 'placed';
        $order->push(event: new OrderPlaced(item: $item), revision: Revision::initial());

        return $order;
    }

    public function ship(string $carrier): void
    {
        $this->status = 'shipped';
        $this->push(event: new OrderShipped(carrier: $carrier), revision: Revision::initial());
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    protected function identityName(): string
    {
        return 'orderId';
    }
}
