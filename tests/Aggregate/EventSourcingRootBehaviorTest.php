<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use LogicException;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\CartWithoutHandler;
use Test\TinyBlocks\BuildingBlocks\Models\ExplicitCart;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\ProductAdded;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class EventSourcingRootBehaviorTest extends TestCase
{
    public function testBlankAggregateStartsWithInitialSequenceNumber(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-1');

        /** @When creating a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @Then the aggregate starts at sequence number zero */
        self::assertSame(0, $cart->getSequenceNumber()->value);
    }

    public function testBlankAggregateStartsWithEmptyDomainState(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-1b');

        /** @When creating a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @Then the aggregate's domain state is empty */
        self::assertSame([], $cart->getProductIds());
    }

    public function testBlankAggregateCarriesTheGivenIdentity(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-1c');

        /** @When creating a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @Then the aggregate exposes the given identity */
        self::assertSame($cartId, $cart->getIdentity());
    }

    public function testBlankAggregateStartsWithNoRecordedEvents(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-1d');

        /** @When creating a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @Then the recorded events buffer is empty */
        self::assertTrue($cart->recordedEvents()->isEmpty());
    }

    public function testDomainOperationAppliesStateFromEmittedEvent(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @When adding a product */
        $cart->addProduct(productId: 'prod-1');

        /** @Then the domain state reflects the event */
        self::assertSame(['prod-1'], $cart->getProductIds());
    }

    public function testDomainOperationAdvancesSequenceNumber(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When adding a product */
        $cart->addProduct(productId: 'prod-1');

        /** @And adding a second product */
        $cart->addProduct(productId: 'prod-2');

        /** @Then the sequence number equals the number of events */
        self::assertSame(2, $cart->getSequenceNumber()->value);
    }

    public function testDomainOperationAppendsToRecordedEvents(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));

        /** @When adding a product */
        $cart->addProduct(productId: 'prod-1');

        /** @Then one event is recorded */
        self::assertSame(1, $cart->recordedEvents()->count());
    }

    public function testFirstRecordedEventCarriesEnvelopeMetadata(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-5');

        /** @And a blank cart initialized */
        $cart = Cart::blank(identity: $cartId);

        /** @And a product added to the cart */
        $cart->addProduct(productId: 'prod-abc');

        /** @When inspecting the first recorded event */
        $record = $cart->recordedEvents()->first();

        /** @Then the envelope carries the expected metadata */
        self::assertSame('ProductAdded', $record->type->value);
        self::assertSame(1, $record->revision->value);
        self::assertSame(1, $record->sequenceNumber->value);
        self::assertSame('Cart', $record->aggregateType);
        self::assertInstanceOf(ProductAdded::class, $record->event);
        self::assertSame($cartId, $record->identity);
        self::assertSame('prod-abc', $record->event->productId);
    }

    public function testReconstituteReplaysEventsInOrder(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-6');

        /** @And a cart with two products added */
        $original = Cart::withProducts(cartId: $cartId, count: 2);

        /** @When reconstituting from the event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the replayed state preserves event order */
        self::assertSame(['prod-1', 'prod-2'], $reconstituted->getProductIds());
    }

    public function testReconstitutePreservesEventOrderForDistinctivelyOrderedStream(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-6b');

        /** @And a blank cart */
        $original = Cart::blank(identity: $cartId);

        /** @And a product added named zebra */
        $original->addProduct(productId: 'zebra');

        /** @And a product added named apple */
        $original->addProduct(productId: 'apple');

        /** @And a product added named mango */
        $original->addProduct(productId: 'mango');

        /** @When reconstituting from the event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the replayed state preserves the exact insertion order */
        self::assertSame(['zebra', 'apple', 'mango'], $reconstituted->getProductIds());
    }

    public function testReconstituteAdvancesSequenceNumberToLastEvent(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-6c');

        /** @And a cart with two products added */
        $original = Cart::withProducts(cartId: $cartId, count: 2);

        /** @When reconstituting from the event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the sequence number equals the last event's */
        self::assertSame(2, $reconstituted->getSequenceNumber()->value);
    }

    public function testReconstituteWithEmptyStreamYieldsBlankState(): void
    {
        /** @Given a cart identity and no events */
        $cartId = new CartId(value: 'cart-7');

        /** @When reconstituting with no events */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: []);

        /** @Then the state matches a blank aggregate */
        self::assertSame([], $reconstituted->getProductIds());
    }

    public function testReconstituteWithEmptyStreamYieldsInitialSequenceNumber(): void
    {
        /** @Given a cart identity and no events */
        $cartId = new CartId(value: 'cart-7b');

        /** @When reconstituting with no events */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: []);

        /** @Then the sequence number remains at the initial value */
        self::assertSame(0, $reconstituted->getSequenceNumber()->value);
    }

    public function testReconstituteFromSnapshotRestoresDomainState(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-8');

        /** @And a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @And a product added */
        $cart->addProduct(productId: 'prod-snapshot');

        /** @And a snapshot taken at that point */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @When reconstituting from the snapshot only */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the domain state is fully restored */
        self::assertSame(['prod-snapshot'], $reconstituted->getProductIds());
    }

    public function testReconstituteFromSnapshotAppliesTheSnapshotSequenceNumber(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-8b');

        /** @And a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @And a product added */
        $cart->addProduct(productId: 'prod-snapshot');

        /** @And a snapshot taken at that point */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @When reconstituting from the snapshot only */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the sequence number matches the snapshot's */
        self::assertSame(1, $reconstituted->getSequenceNumber()->value);
    }

    public function testReconstituteCombinesSnapshotWithLaterEvents(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-8c');

        /** @And a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a snapshot taken after the first product */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @And a second product added after the snapshot */
        $cart->addProduct(productId: 'prod-2');

        /** @And the records after the snapshot filtered out */
        $laterRecords = $cart->recordedEvents()->filter(
            predicates: static fn($record): bool => $record->sequenceNumber->isAfter(
                other: $snapshot->getSequenceNumber()
            )
        );

        /** @When reconstituting from the snapshot and the later records */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $laterRecords, snapshot: $snapshot);

        /** @Then the full state is restored */
        self::assertSame(['prod-1', 'prod-2'], $reconstituted->getProductIds());
    }

    public function testReconstituteCombinedWithSnapshotAndLaterEventsAdvancesSequence(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-8d');

        /** @And a blank cart */
        $cart = Cart::blank(identity: $cartId);

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a snapshot taken after the first product */
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @And a second product added after the snapshot */
        $cart->addProduct(productId: 'prod-2');

        /** @And the records after the snapshot filtered out */
        $laterRecords = $cart->recordedEvents()->filter(
            predicates: static fn($record): bool => $record->sequenceNumber->isAfter(
                other: $snapshot->getSequenceNumber()
            )
        );

        /** @When reconstituting from the snapshot and the later records */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $laterRecords, snapshot: $snapshot);

        /** @Then the sequence number reflects the last applied event */
        self::assertSame(2, $reconstituted->getSequenceNumber()->value);
    }

    public function testReconstitutedAggregateHasNoRecordedEvents(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-9');

        /** @And a cart with one product added */
        $original = Cart::withProducts(cartId: $cartId, count: 1);

        /** @When reconstituting from that event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the reconstituted aggregate has no fresh recorded events */
        self::assertTrue($reconstituted->recordedEvents()->isEmpty());
    }

    public function testExplicitHandlerIsInvokedForRegisteredEvent(): void
    {
        /** @Given a blank ExplicitCart */
        $cart = ExplicitCart::blank(identity: new CartId(value: 'cart-explicit-1'));

        /** @When adding a product via the explicit handler path */
        $cart->addProduct(productId: 'prod-explicit');

        /** @Then the product appears in the aggregate state */
        self::assertSame(['prod-explicit'], $cart->getProductIds());
    }

    public function testRevisionOverrideIsCarriedOnEventRecord(): void
    {
        /** @Given a blank ExplicitCart */
        $cart = ExplicitCart::blank(identity: new CartId(value: 'cart-explicit-2'));

        /** @When adding a v2 product whose event overrides revision */
        $cart->addProductV2(productId: 'prod-v2', quantity: 3);

        /** @Then the recorded event carries revision 2 */
        self::assertSame(2, $cart->recordedEvents()->first()->revision->value);
    }

    public function testExplicitCartThrowsForUnregisteredEvent(): void
    {
        /** @Given an ExplicitCart identity */
        $cartId = new CartId(value: 'cart-explicit-err');

        /** @And an OrderPlaced record from a foreign aggregate */
        $orderRecords = Order::place(orderId: new OrderId(value: 'ord-err'), item: 'book')->recordedEvents();

        /** @Then a LogicException naming the unregistered event should be thrown */
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'No handler registered for event <%s> in aggregate <%s>.',
                OrderPlaced::class,
                ExplicitCart::class
            )
        );

        /** @When reconstituting ExplicitCart from the OrderPlaced records */
        ExplicitCart::reconstitute(identity: $cartId, records: $orderRecords);
    }

    public function testReconstituteThrowsWhenHandlerMethodIsMissing(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-10');

        /** @And a cart with one product added */
        $original = Cart::withProducts(cartId: $cartId, count: 1);

        /** @Then a LogicException pointing to the missing handler should be thrown */
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Handler method <whenProductAdded> not found in aggregate <%s>.',
                CartWithoutHandler::class
            )
        );

        /** @When reconstituting an aggregate without the handler */
        CartWithoutHandler::reconstitute(identity: $cartId, records: $original->recordedEvents());
    }
}
