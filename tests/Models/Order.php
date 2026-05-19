<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use InvalidArgumentException;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Entity\Identity;

final class Order implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $status = 'draft';

    private function __construct(private readonly OrderId $id)
    {
    }

    public static function reconstitute(
        Identity $identity,
        AggregateVersion $aggregateVersion,
        array $state = []
    ): static {
        if (!$identity instanceof OrderId) {
            $template = 'Expected identity of type <%s>, got <%s>.';

            throw new InvalidArgumentException(message: sprintf($template, OrderId::class, $identity::class));
        }

        $order = new Order(id: $identity);
        $order->aggregateVersion = $aggregateVersion;

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
