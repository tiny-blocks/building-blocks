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

    /** @var list<string> */
    private array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->logBuffer .= "Added: {$productId}";
        $this->when(event: new ProductAdded(productId: $productId));
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $this->productIds = $snapshot->getAggregateState()['productIds'] ?? [];
    }

    public function getSnapshotState(): array
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents'], $state['sequenceNumber'], $state['logBuffer']);

        return $state;
    }

    /**
     * @return list<string>
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    protected function identityName(): string
    {
        return 'cartId';
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
