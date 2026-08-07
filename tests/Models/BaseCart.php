<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

class BaseCart implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    protected CartId $id;

    protected array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->when(event: new ProductAdded(productId: $productId));
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $this->productIds = ($snapshot->aggregateState()['productIds'] ?? []);
    }

    public function productIds(): array
    {
        return $this->productIds;
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
