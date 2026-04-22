<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
use TinyBlocks\Time\Instant;

final class SnapshotTest extends TestCase
{
    public function testFromAggregateCapturesAggregateType(): void
    {
        /** @Given a cart with some state */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));
        $cart->addProduct(productId: 'prod-1');

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the type matches the aggregate's short class name */
        self::assertSame('Cart', $snapshot->getType());
    }

    public function testFromAggregateCapturesAggregateId(): void
    {
        /** @Given a cart with a known identity */
        $cart = Cart::blank(identity: new CartId(value: 'cart-id-42'));

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the aggregate id reflects the identity value */
        self::assertSame('cart-id-42', $snapshot->getAggregateId());
    }

    public function testFromAggregateCapturesSequenceNumber(): void
    {
        /** @Given a cart with two events applied */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));
        $cart->addProduct(productId: 'prod-1');
        $cart->addProduct(productId: 'prod-2');

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the sequence number is captured */
        self::assertSame(2, $snapshot->getSequenceNumber()->value);
    }

    public function testFromAggregateCapturesCreatedAt(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the createdAt timestamp is set */
        self::assertInstanceOf(Instant::class, $snapshot->getCreatedAt());
    }

    public function testFromAggregateCarriesDomainFieldsInState(): void
    {
        /** @Given a cart with a product added */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->getAggregateState();

        /** @Then the state carries the domain fields */
        self::assertSame(['prod-x'], $state['productIds']);
    }

    public function testFromAggregateStateOmitsRecordedEventsBuffer(): void
    {
        /** @Given a cart with a product added */
        $cart = Cart::blank(identity: new CartId(value: 'cart-5'));
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->getAggregateState();

        /** @Then the transient recording buffer is not part of the persisted state */
        self::assertArrayNotHasKey('recordedEvents', $state);
    }

    public function testFromAggregateStateOmitsSequenceNumber(): void
    {
        /** @Given a cart with a product added */
        $cart = Cart::blank(identity: new CartId(value: 'cart-6'));
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->getAggregateState();

        /** @Then the sequence number is not duplicated into the state */
        self::assertArrayNotHasKey('sequenceNumber', $state);
    }

    public function testRoundTripThroughSnapshotRestoresDomainState(): void
    {
        /** @Given a cart with a product added */
        $cartId = new CartId(value: 'cart-7');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'prod-roundtrip');

        /** @When taking a snapshot and reconstituting a fresh aggregate from it */
        $snapshot = Snapshot::fromAggregate(aggregate: $original);
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the reconstituted aggregate carries the same domain state */
        self::assertSame(['prod-roundtrip'], $reconstituted->getProductIds());
    }

    public function testEqualsReturnsTrueForIdenticallyBuiltSnapshots(): void
    {
        /** @Given shared fields for two snapshots */
        $sequenceNumber = SequenceNumber::first();
        $createdAt = Instant::now();

        /** @And two snapshots built from those identical fields */
        $first = new Snapshot(
            type: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: ['productIds' => []],
            sequenceNumber: $sequenceNumber
        );
        $second = new Snapshot(
            type: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: ['productIds' => []],
            sequenceNumber: $sequenceNumber
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseWhenAnyFieldDiffers(): void
    {
        /** @Given two snapshots that differ only by type */
        $sequenceNumber = SequenceNumber::first();
        $createdAt = Instant::now();

        /** @And the two snapshots constructed accordingly */
        $first = new Snapshot(
            type: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: [],
            sequenceNumber: $sequenceNumber
        );
        $second = new Snapshot(
            type: 'Order',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: [],
            sequenceNumber: $sequenceNumber
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }
}
