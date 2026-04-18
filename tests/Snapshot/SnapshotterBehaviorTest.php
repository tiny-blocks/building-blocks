<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\FileSnapshotter;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class SnapshotterBehaviorTest extends TestCase
{
    public function testTakePersistsASnapshotForTheAggregate(): void
    {
        /** @Given a cart with state and a fresh snapshotter */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));
        $cart->addProduct(productId: 'prod-1');
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then a Snapshot is persisted */
        self::assertInstanceOf(Snapshot::class, $snapshotter->lastSnapshot());
    }

    public function testPersistedSnapshotReflectsTheAggregateType(): void
    {
        /** @Given a cart and a fresh snapshotter */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate's type */
        self::assertSame('Cart', $snapshotter->lastSnapshot()->getType());
    }

    public function testPersistedSnapshotReflectsTheAggregateSequenceNumber(): void
    {
        /** @Given a cart advanced to sequence number 2 */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));
        $cart->addProduct(productId: 'prod-1');
        $cart->addProduct(productId: 'prod-2');
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate's sequence number */
        self::assertSame(2, $snapshotter->lastSnapshot()->getSequenceNumber()->value);
    }

    public function testPersistedSnapshotReflectsTheAggregateIdentity(): void
    {
        /** @Given a cart with a known identity */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));
        $snapshotter = new FileSnapshotter();

        /** @When taking a snapshot */
        $snapshotter->take(aggregate: $cart);

        /** @Then the persisted snapshot carries the aggregate id */
        self::assertSame('cart-4', $snapshotter->lastSnapshot()->getAggregateId());
    }
}
