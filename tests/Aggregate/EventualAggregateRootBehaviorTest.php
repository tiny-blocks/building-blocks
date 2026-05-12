<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\OrderShipped;

final class EventualAggregateRootBehaviorTest extends TestCase
{
    public function testSequenceNumberIsOneAfterSinglePlacement(): void
    {
        /** @Given an order that emits a single event on creation */
        $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'book');

        /** @When retrieving the sequence number */
        $sequenceNumber = $order->sequenceNumber();

        /** @Then the sequence number is 1 */
        self::assertSame(1, $sequenceNumber->value);
    }

    public function testSequenceNumberAdvancesOnEverySubsequentEvent(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'pen');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'DHL');

        /** @When retrieving the sequence number */
        $sequenceNumber = $order->sequenceNumber();

        /** @Then the sequence number reflects every emitted event */
        self::assertSame(2, $sequenceNumber->value);
    }

    public function testRecordedEventsCountMatchesEmittedEvents(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-3'), item: 'lamp');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'FedEx');

        /** @When retrieving recorded events */
        $records = $order->recordedEvents();

        /** @Then the count matches the number of events */
        self::assertSame(2, $records->count());
    }

    public function testFirstRecordedEventCarriesPlacementMetadata(): void
    {
        /** @Given an identity for the placed order */
        $orderId = new OrderId(value: 'ord-4');

        /** @And a placed order emitting an OrderPlaced event */
        $order = Order::place(orderId: $orderId, item: 'chair');

        /** @When inspecting the first recorded record */
        $record = $order->recordedEvents()->first();

        /** @Then the envelope carries the placement metadata */
        self::assertSame('OrderPlaced', $record->type->value);
        self::assertSame(1, $record->revision->value);
        self::assertSame(1, $record->sequenceNumber->value);
        self::assertSame('Order', $record->aggregateType);
        self::assertInstanceOf(OrderPlaced::class, $record->event);
        self::assertSame($orderId, $record->identity);
        self::assertSame('chair', $record->event->item);
    }

    public function testSecondRecordedEventCarriesShippingMetadata(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-4b'), item: 'chair');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'UPS');

        /** @When inspecting the last recorded record */
        $record = $order->recordedEvents()->last();

        /** @Then the envelope carries the shipping metadata */
        self::assertSame('OrderShipped', $record->type->value);
        self::assertSame(2, $record->sequenceNumber->value);
        self::assertInstanceOf(OrderShipped::class, $record->event);
        self::assertSame('UPS', $record->event->carrier);
    }

    public function testRecordedEventsReturnsIndependentCopyOnEachCall(): void
    {
        /** @Given an order with one recorded event */
        $order = Order::place(orderId: new OrderId(value: 'ord-6'), item: 'mug');

        /** @And an external mutation applied to the first retrieved copy */
        $order->recordedEvents()->add($order->recordedEvents()->first());

        /** @When retrieving the recorded events again */
        $secondCopy = $order->recordedEvents();

        /** @Then the aggregate's own buffer is unaffected by the external mutation */
        self::assertSame(1, $secondCopy->count());
    }

    public function testBufferAccumulatesAcrossOperationsWithoutClearing(): void
    {
        /** @Given a placed order whose events are still buffered */
        $order = Order::place(orderId: new OrderId(value: 'ord-7'), item: 'bottle');

        /** @And the buffer drained without clearing, simulating a save that reads but does not reset */
        $firstBatch = $order->recordedEvents();

        /** @When a second operation emits a further event on the same instance */
        $order->ship(carrier: 'DHL');

        /** @Then the buffer accumulates events from both operations */
        self::assertSame(2, $order->recordedEvents()->count());
        self::assertSame(1, $firstBatch->count());
    }

    public function testSnapshotDataCapturesDomainStateOnEveryEvent(): void
    {
        /** @Given an order that transitioned to 'placed' */
        $order = Order::place(orderId: new OrderId(value: 'ord-9'), item: 'tray');

        /** @When inspecting the snapshot data carried by the event record */
        $state = $order->recordedEvents()->first()->snapshotData->toArray();

        /** @Then the domain status field is captured with its current value */
        self::assertSame('placed', $state['status']);
    }

    public function testSnapshotDataOmitsTransientRecordedEventsBuffer(): void
    {
        /** @Given an order that emits a placement event */
        $order = Order::place(orderId: new OrderId(value: 'ord-10'), item: 'tray');

        /** @When inspecting the persistable state attached to the record */
        $state = $order->recordedEvents()->first()->snapshotData->toArray();

        /** @Then the recording buffer is not part of the persisted state */
        self::assertArrayNotHasKey('recordedEvents', $state);
    }

    public function testSnapshotDataOmitsSequenceNumber(): void
    {
        /** @Given an order that emits a placement event */
        $order = Order::place(orderId: new OrderId(value: 'ord-11'), item: 'mug');

        /** @When inspecting the persistable state attached to the record */
        $state = $order->recordedEvents()->first()->snapshotData->toArray();

        /** @Then the sequence number is not duplicated in the snapshot payload */
        self::assertArrayNotHasKey('sequenceNumber', $state);
    }

    public function testSnapshotDataContainsAllDomainFields(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-12'), item: 'desk');

        /** @When reading the snapshot payload from the first event record */
        $state = $order->recordedEvents()->first()->snapshotData->toArray();

        /** @Then all domain fields are present in the payload */
        self::assertArrayHasKey('id', $state);
        self::assertArrayHasKey('status', $state);
    }
}
