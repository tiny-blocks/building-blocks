<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
use TinyBlocks\BuildingBlocks\Utc;

final class Cart implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    private CartId $cartId;

    private array $productIds = [];

    public static function withProducts(CartId $cartId, int $count): Cart
    {
        $cart = Cart::blank(identity: $cartId);
        for ($index = 1; $index <= $count; $index++) {
            $cart->addProduct(productId: sprintf('prod-%d', $index));
        }

        return $cart;
    }

    public function addProduct(string $productId, ?Utc $addedAt = null): void
    {
        $this->when(event: new ProductAdded(productId: $productId), occurredAt: $addedAt);
    }

    public function applySnapshot(Snapshot $snapshot): void
    {
        $state = $snapshot->aggregateState();
        $this->productIds = ($state['productIds'] ?? []);
    }

    public function productIds(): array
    {
        return $this->productIds;
    }

    protected function identityProperty(): string
    {
        return 'cartId';
    }

    public function modelVersion(): ModelVersion
    {
        return ModelVersion::of(value: 1);
    }

    protected function whenProductAdded(ProductAdded $event): void
    {
        $this->productIds[] = $event->productId;
    }
}
