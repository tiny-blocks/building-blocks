<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class CartWithLogger implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    private CartId $cartId;

    private string $logBuffer = '';

    private array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->logBuffer .= "Added: $productId";
        $this->when(event: new ProductAdded(productId: $productId));
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $this->productIds = $snapshot->aggregateState()['productIds'] ?? [];
    }

    public function snapshotState(): array
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents'], $state['aggregateVersion'], $state['logBuffer']);

        return $state;
    }

    protected function identityProperty(): string
    {
        return 'cartId';
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
