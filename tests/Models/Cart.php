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

    private CartId $cartId;

    /** @var list<string> */
    private array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->when(event: new ProductAdded(productId: $productId), revision: Revision::initial());
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

    protected function identityName(): string
    {
        return 'cartId';
    }

    protected function modelVersion(): int
    {
        return 1;
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
