<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class ExplicitCart implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    private CartId $cartId;

    private array $productIds = [];

    public function addProduct(string $productId): void
    {
        $this->when(event: new ProductAdded(productId: $productId));
    }

    public function addProductV2(string $productId, int $quantity): void
    {
        $this->when(event: new ProductAddedV2(productId: $productId, quantity: $quantity));
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $this->productIds = ($snapshot->aggregateState()['productIds'] ?? []);
    }

    public function eventHandlers(): array
    {
        return [
            ProductAdded::class   => function (ProductAdded $event): void {
                $this->productIds[] = $event->productId;
            },
            ProductAddedV2::class => function (ProductAddedV2 $event): void {
                $this->productIds[] = $event->productId;
            }
        ];
    }

    public function productIds(): array
    {
        return $this->productIds;
    }

    protected function identityProperty(): string
    {
        return 'cartId';
    }
}
