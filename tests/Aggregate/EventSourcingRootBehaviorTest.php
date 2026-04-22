<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use LogicException;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\CartWithoutHandler;
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

        /** @And two products added in sequence */
        $cart->addProduct(productId: 'prod-1');
        $cart->addProduct(productId: 'prod-2');

        /** @When retrieving the sequence number */
        $sequenceNumber = $cart->getSequenceNumber();

        /** @Then the sequence number equals the number of events */
        self::assertSame(2, $sequenceNumber->value);
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
        /** @Given a blank cart with a known identity */
        $cartId = new CartId(value: 'cart-5');

        /** @And a product added to the cart */
        $cart = Cart::blank(identity: $cartId);
        $cart->addProduct(productId: 'prod-abc');

        /** @When inspecting the first recorded record */
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
        /** @Given a cart with two products added */
        $cartId = new CartId(value: 'cart-6');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'prod-1');
        $original->addProduct(productId: 'prod-2');

        /** @When reconstituting from the event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the replayed state preserves event order */
        self::assertSame(['prod-1', 'prod-2'], $reconstituted->getProductIds());
    }

    public function testReconstitutePreservesEventOrderForDistinctivelyOrderedStream(): void
    {
        /** @Given a cart that received products in a distinctive order */
        $cartId = new CartId(value: 'cart-6b');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'zebra');
        $original->addProduct(productId: 'apple');
        $original->addProduct(productId: 'mango');

        /** @When reconstituting from the event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the replayed state preserves the exact insertion order */
        self::assertSame(['zebra', 'apple', 'mango'], $reconstituted->getProductIds());
    }

    public function testReconstituteAdvancesSequenceNumberToLastEvent(): void
    {
        /** @Given a cart with two products added */
        $cartId = new CartId(value: 'cart-6c');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'prod-1');
        $original->addProduct(productId: 'prod-2');

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
        /** @Given a cart with one product and a snapshot taken at that point */
        $cartId = new CartId(value: 'cart-8');
        $cart = Cart::blank(identity: $cartId);
        $cart->addProduct(productId: 'prod-snapshot');
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @When reconstituting from the snapshot only */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the domain state is fully restored */
        self::assertSame(['prod-snapshot'], $reconstituted->getProductIds());
    }

    public function testReconstituteFromSnapshotAppliesTheSnapshotSequenceNumber(): void
    {
        /** @Given a cart with one product and a snapshot taken at that point */
        $cartId = new CartId(value: 'cart-8b');
        $cart = Cart::blank(identity: $cartId);
        $cart->addProduct(productId: 'prod-snapshot');
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);

        /** @When reconstituting from the snapshot only */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: [], snapshot: $snapshot);

        /** @Then the sequence number matches the snapshot's */
        self::assertSame(1, $reconstituted->getSequenceNumber()->value);
    }

    public function testReconstituteCombinesSnapshotWithLaterEvents(): void
    {
        /** @Given a cart snapshotted after one product, then more events after the snapshot */
        $cartId = new CartId(value: 'cart-8c');
        $cart = Cart::blank(identity: $cartId);
        $cart->addProduct(productId: 'prod-1');
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);
        $cart->addProduct(productId: 'prod-2');
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
        /** @Given a cart snapshotted after one product, then more events after the snapshot */
        $cartId = new CartId(value: 'cart-8d');
        $cart = Cart::blank(identity: $cartId);
        $cart->addProduct(productId: 'prod-1');
        $snapshot = Snapshot::fromAggregate(aggregate: $cart);
        $cart->addProduct(productId: 'prod-2');
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
        /** @Given a cart with one recorded event */
        $cartId = new CartId(value: 'cart-9');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'prod-1');

        /** @When reconstituting from that event stream */
        $reconstituted = Cart::reconstitute(identity: $cartId, records: $original->recordedEvents());

        /** @Then the reconstituted aggregate has no fresh recorded events */
        self::assertTrue($reconstituted->recordedEvents()->isEmpty());
    }

    public function testReconstituteThrowsWhenHandlerMethodIsMissing(): void
    {
        /** @Given a recorded event whose aggregate has no matching when handler */
        $cartId = new CartId(value: 'cart-10');
        $original = Cart::blank(identity: $cartId);
        $original->addProduct(productId: 'prod-x');

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
