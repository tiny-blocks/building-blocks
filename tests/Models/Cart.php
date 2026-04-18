<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class Cart implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    private const string IDENTITY = 'cartId';
    private const int MODEL_VERSION = 1;

    private CartId $cartId;

    /** @var list<string> */
    private array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->when(event: new ProductAdded(productId: $productId), revision: new Revision(value: 1));
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $state = $snapshot->getAggregateState();
        $this->productIds = $state['productIds'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
