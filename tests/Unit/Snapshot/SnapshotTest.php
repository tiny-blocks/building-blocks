<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\CartWithLogger;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
use TinyBlocks\Time\Instant;

final class SnapshotTest extends TestCase
{
    public function testFromAggregateCapturesAggregateType(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @And a product added */
        $cart->addProduct(productId: 'prod-1');

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the aggregate type matches the aggregate's short class name */
        self::assertSame('Cart', $snapshot->aggregateType());
    }

    public function testFromAggregateCapturesAggregateId(): void
    {
        /** @Given a cart with a known identity */
        $cart = Cart::blank(identity: new CartId(value: 'cart-id-42'));

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the aggregate id reflects the identity value */
        self::assertSame('cart-id-42', $snapshot->aggregateId());
    }

    public function testFromAggregateCapturesAggregateVersion(): void
    {
        /** @Given a cart with two products added */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-2'), count: 2);

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the aggregate version is captured */
        self::assertSame(2, $snapshot->aggregateVersion()->value);
    }

    public function testFromAggregateCapturesCreatedAt(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When taking a snapshot */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @Then the createdAt timestamp is set */
        self::assertInstanceOf(Instant::class, $snapshot->createdAt());
    }

    public function testFromAggregateCarriesDomainFieldsInState(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));

        /** @And a product added */
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->aggregateState();

        /** @Then the state carries the domain fields */
        self::assertSame(['prod-x'], $state['productIds']);
    }

    public function testFromAggregateStateOmitsRecordedEventsBuffer(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-5'));

        /** @And a product added */
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->aggregateState();

        /** @Then the transient recording buffer is not part of the persisted state */
        self::assertArrayNotHasKey('recordedEvents', $state);
    }

    public function testFromAggregateStateOmitsAggregateVersion(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-6'));

        /** @And a product added */
        $cart->addProduct(productId: 'prod-x');

        /** @When taking a snapshot */
        $state = Snapshot::fromAggregate(aggregate: $cart)->aggregateState();

        /** @Then the aggregate version is not duplicated into the state */
        self::assertArrayNotHasKey('aggregateVersion', $state);
    }

    public function testRoundTripThroughSnapshotRestoresDomainState(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-7');

        /** @And a blank cart */
        $original = Cart::blank(identity: $cartId);

        /** @And a product added */
        $original->addProduct(productId: 'prod-roundtrip');

        /** @And a snapshot taken */
        $snapshot = Snapshot::fromAggregate(aggregate: $original);

        /** @When reconstituting from the snapshot */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the reconstituted aggregate carries the same domain state */
        self::assertSame(['prod-roundtrip'], $reconstituted->productIds());
    }

    public function testSnapshotStateExcludesInfrastructureProperty(): void
    {
        /** @Given a blank cart with a logger */
        $cart = CartWithLogger::blank(identity: new CartId(value: 'cart-logger-1'));

        /** @When adding a product (which also writes to the log buffer) */
        $cart->addProduct(productId: 'prod-1');

        /** @Then the snapshot state does not contain the log buffer */
        self::assertArrayNotHasKey('logBuffer', $cart->snapshotState());
    }

    public function testSnapshotStateIncludesDomainFields(): void
    {
        /** @Given a blank cart with a logger */
        $cart = CartWithLogger::blank(identity: new CartId(value: 'cart-logger-2'));

        /** @When adding a product */
        $cart->addProduct(productId: 'prod-snapshot');

        /** @Then the snapshot state includes the domain fields */
        self::assertSame(['prod-snapshot'], $cart->snapshotState()['productIds']);
    }

    public function testFromAggregateWithOverriddenSnapshotStateExcludesInfrastructureProperty(): void
    {
        /** @Given a blank cart with a logger */
        $cart = CartWithLogger::blank(identity: new CartId(value: 'cart-logger-3'));

        /** @When adding a product and taking a snapshot */
        $cart->addProduct(productId: 'prod-x');

        /** @Then the snapshot does not carry the log buffer in the aggregate state */
        self::assertArrayNotHasKey('logBuffer', Snapshot::fromAggregate(aggregate: $cart)->aggregateState());
    }

    public function testEqualsReturnsTrueForIdenticallyBuiltSnapshots(): void
    {
        /** @Given an aggregate version at its first value */
        $aggregateVersion = AggregateVersion::first();

        /** @And a known creation timestamp */
        $createdAt = Instant::now();

        /** @And the first snapshot built from those fields */
        $first = Snapshot::restore(
            aggregateType: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: ['productIds' => []],
            aggregateVersion: $aggregateVersion
        );

        /** @And the second snapshot built from the same fields */
        $second = Snapshot::restore(
            aggregateType: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: ['productIds' => []],
            aggregateVersion: $aggregateVersion
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseWhenAnyFieldDiffers(): void
    {
        /** @Given an aggregate version at its first value */
        $aggregateVersion = AggregateVersion::first();

        /** @And a known creation timestamp */
        $createdAt = Instant::now();

        /** @And the first snapshot with type Cart */
        $first = Snapshot::restore(
            aggregateType: 'Cart',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: [],
            aggregateVersion: $aggregateVersion
        );

        /** @And the second snapshot with type Order */
        $second = Snapshot::restore(
            aggregateType: 'Order',
            createdAt: $createdAt,
            aggregateId: 'cart-1',
            aggregateState: [],
            aggregateVersion: $aggregateVersion
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }
}
