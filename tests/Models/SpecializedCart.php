<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

final class SpecializedCart extends BaseCart
{
    public static function startEmpty(CartId $cartId): SpecializedCart
    {
        return static::createBlank(identity: $cartId);
    }

    public function addGiftProduct(string $productId): void
    {
        $this->when(event: new ProductAdded(productId: $productId));
    }

    public function identityPropertyName(): string
    {
        return $this->identityProperty();
    }
}
