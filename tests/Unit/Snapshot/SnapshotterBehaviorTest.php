<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Unit\FileSnapshotter;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class SnapshotterBehaviorTest extends TestCase
{
    public function testTakePersistsASnapshotForTheAggregate(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @And a product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a fresh snapshotter */
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then a Snapshot is persisted */
        self::assertInstanceOf(Snapshot::class, $snapshotter->lastSnapshot());
    }

    public function testPersistedSnapshotReflectsTheAggregateType(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @And a fresh snapshotter */
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate's type */
        self::assertSame('Cart', $snapshotter->lastSnapshot()?->aggregateType());
    }

    public function testPersistedSnapshotReflectsTheAggregateVersion(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a second product added */
        $cart->addProduct(productId: 'prod-2');

        /** @And a fresh snapshotter */
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate's version */
        self::assertSame(2, $snapshotter->lastSnapshot()?->aggregateVersion()->value);
    }

    public function testPersistedSnapshotReflectsTheAggregateIdentity(): void
    {
        /** @Given a blank cart with a known identity */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));

        /** @And a fresh snapshotter */
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate id */
        self::assertSame('cart-4', $snapshotter->lastSnapshot()?->aggregateId());
    }
}
