<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Aggregate;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\TinyBlocks\BuildingBlocks\Models\GuestReservation;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\OrderShipped;
use Test\TinyBlocks\BuildingBlocks\Models\Reservation;
use Test\TinyBlocks\BuildingBlocks\Models\ReservationId;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Exceptions\IncompleteAggregateState;

final class EventualAggregateRootBehaviorTest extends TestCase
{
    public function testAggregateVersionIsOneAfterSinglePlacement(): void
    {
        /** @Given an order that emits a single event on creation */
        $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'book');

        /** @When retrieving the aggregate version */
        $aggregateVersion = $order->aggregateVersion();

        /** @Then the aggregate version is 1 */
        self::assertSame(1, $aggregateVersion->value);
    }

    public function testAggregateVersionAdvancesOnEverySubsequentEvent(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'pen');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'DHL');

        /** @When retrieving the aggregate version */
        $aggregateVersion = $order->aggregateVersion();

        /** @Then the aggregate version reflects every emitted event */
        self::assertSame(2, $aggregateVersion->value);
    }

    public function testRecordedEventsCountMatchesEmittedEvents(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-3'), item: 'lamp');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'FedEx');

        /** @When retrieving recorded events */
        $records = $order->peekEvents();

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
        $record = $order->peekEvents()->first();

        /** @Then the envelope carries the placement metadata */
        self::assertSame('OrderPlaced', $record->eventType->value);
        self::assertSame(1, $record->revision->value);
        self::assertSame(1, $record->aggregateVersion->value);
        self::assertSame('Order', $record->aggregateType);
        self::assertInstanceOf(OrderPlaced::class, $record->event);
        self::assertSame($orderId, $record->aggregateId);
        self::assertSame('chair', $record->event->item);
    }

    public function testSecondRecordedEventCarriesShippingMetadata(): void
    {
        /** @Given a placed order */
        $order = Order::place(orderId: new OrderId(value: 'ord-4b'), item: 'chair');

        /** @And a shipping event emitted after placement */
        $order->ship(carrier: 'UPS');

        /** @When inspecting the last recorded record */
        $record = $order->peekEvents()->last();

        /** @Then the envelope carries the shipping metadata */
        self::assertSame('OrderShipped', $record->eventType->value);
        self::assertSame(2, $record->aggregateVersion->value);
        self::assertInstanceOf(OrderShipped::class, $record->event);
        self::assertSame('UPS', $record->event->carrier);
    }

    public function testRecordedEventsReturnsIndependentCopyOnEachCall(): void
    {
        /** @Given an order with one recorded event */
        $order = Order::place(orderId: new OrderId(value: 'ord-6'), item: 'mug');

        /** @And an external mutation applied to the first retrieved copy */
        $order->peekEvents()->merge(other: $order->peekEvents());

        /** @When retrieving the recorded events again */
        $secondCopy = $order->peekEvents();

        /** @Then the aggregate's own buffer is unaffected by the external mutation */
        self::assertSame(1, $secondCopy->count());
    }

    public function testBufferAccumulatesAcrossOperationsWithoutClearing(): void
    {
        /** @Given a placed order whose events are still buffered */
        $order = Order::place(orderId: new OrderId(value: 'ord-7'), item: 'bottle');

        /** @And the buffer drained without clearing, simulating a save that reads but does not reset */
        $firstBatch = $order->peekEvents();

        /** @When a second operation emits a further event on the same instance */
        $order->ship(carrier: 'DHL');

        /** @Then the buffer accumulates events from both operations */
        self::assertSame(2, $order->peekEvents()->count());
        self::assertSame(1, $firstBatch->count());
    }

    public function testReconstituteRestoresIdentityWhenNoStateIsProvided(): void
    {
        /** @Given an identity to assign to the reconstituted aggregate */
        $reservationId = new ReservationId(value: 'res-1');

        /** @When reconstituting via the trait default with no state */
        $reservation = Reservation::reconstitutePartial(
            identity: $reservationId,
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @Then the identity is restored on the reconstituted aggregate */
        self::assertTrue($reservation->identity()->equals(other: $reservationId));
    }

    public function testReconstituteInitializesEmptyRecordedEventsBuffer(): void
    {
        /** @Given a reservation reconstituted via the trait default */
        $reservation = Reservation::reconstitutePartial(
            identity: new ReservationId(value: 'res-1'),
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @When retrieving the recorded events */
        $records = $reservation->peekEvents();

        /** @Then the buffer starts empty */
        self::assertTrue($records->isEmpty());
    }

    public function testReconstituteRestoresAggregateVersionForNextEvent(): void
    {
        /** @Given a reservation reconstituted at version 5 with pending status */
        $reservation = Reservation::reconstitutePartial(
            identity: new ReservationId(value: 'res-1'),
            aggregateState: ['status' => 'pending'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @When confirming the reservation */
        $reservation->confirm();

        /** @Then the next recorded event carries version 6 */
        self::assertSame(6, $reservation->peekEvents()->first()->aggregateVersion->value);
    }

    public function testReconstituteHydratesStateSoCommandsBehaveCorrectly(): void
    {
        /** @Given a reservation reconstituted in confirmed status */
        $reservation = Reservation::reconstitutePartial(
            identity: new ReservationId(value: 'res-1'),
            aggregateState: ['status' => 'confirmed'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @Then a RuntimeException is raised because state was correctly restored */
        $this->expectException(RuntimeException::class);

        /** @When attempting to confirm an already confirmed reservation */
        $reservation->confirm();
    }

    public function testReconstituteSilentlyIgnoresUnknownStateKeys(): void
    {
        /** @Given an identity for the aggregate */
        $reservationId = new ReservationId(value: 'res-1');

        /** @When reconstituting with a state map carrying a key absent from the aggregate */
        $reservation = Reservation::reconstitutePartial(
            identity: $reservationId,
            aggregateState: ['status' => 'pending', 'unknownProperty' => 'value'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @Then no exception is raised and the known identity is still restored */
        self::assertTrue($reservation->identity()->equals(other: $reservationId));
    }

    public function testOrderReconstituteRejectsForeignIdentityType(): void
    {
        /** @Given an identity belonging to a foreign aggregate type */
        $foreignIdentity = new ReservationId(value: 'res-1');

        /** @Then an exception indicating the wrong identity type should be thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When reconstituting an Order with a non-OrderId identity */
        Order::reconstitutePartial(
            identity: $foreignIdentity,
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 1)
        );
    }

    public function testOrderReconstituteRestoresVersionForNextEvent(): void
    {
        /** @Given an Order reconstituted at version 9 */
        $order = Order::reconstitutePartial(
            identity: new OrderId(value: 'ord-rec-1'),
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 9)
        );

        /** @And a shipping event emitted on the reconstituted instance */
        $order->ship(carrier: 'DHL');

        /** @When inspecting the recorded event */
        $record = $order->peekEvents()->first();

        /** @Then the event carries version 10 */
        self::assertSame(10, $record->aggregateVersion->value);
    }

    public function testReconstituteStrictWhenAllRequiredStateProvidedThenMatchesLenientResult(): void
    {
        /** @Given a reservation reconstituted leniently with all required state */
        $lenient = Reservation::reconstitutePartial(
            identity: new ReservationId(value: 'res-1'),
            aggregateState: ['status' => 'pending'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @When reconstituting the same reservation strictly */
        $strict = Reservation::reconstituteStrict(
            identity: new ReservationId(value: 'res-1'),
            aggregateState: ['status' => 'pending'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @Then the strict result equals the lenient result */
        self::assertEquals($lenient, $strict);
    }

    public function testReconstituteStrictWhenRequiredPropertyOmittedThenNamesItIncomplete(): void
    {
        /** @Given an identity for an aggregate reconstituted without its required state */
        $reservationId = new ReservationId(value: 'res-1');

        try {
            /** @When reconstituting strictly with the required status omitted */
            Reservation::reconstituteStrict(
                identity: $reservationId,
                aggregateState: [],
                aggregateVersion: AggregateVersion::of(value: 5)
            );
        } catch (IncompleteAggregateState $exception) {
            /** @Then the exception names the uninitialized required property */
            self::assertSame(['status'], $exception->propertyNames);

            /** @And the message identifies that property */
            self::assertStringContainsString('status', $exception->getMessage());
        }
    }

    public function testReconstituteStrictWhenUnknownKeyProvidedThenStillSucceeds(): void
    {
        /** @Given an identity for an aggregate reconstituted with an unknown state key */
        $reservationId = new ReservationId(value: 'res-1');

        /** @When reconstituting strictly with all required state plus an unknown key */
        $reservation = Reservation::reconstituteStrict(
            identity: $reservationId,
            aggregateState: ['status' => 'pending', 'unknownProperty' => 'value'],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @Then the unknown key is ignored and reconstitution succeeds */
        self::assertTrue($reservation->identity()->equals(other: $reservationId));
    }

    public function testReconstituteStrictWhenPropertyHasDefaultThenNotFlagged(): void
    {
        /** @Given an identity for an Order whose status property carries a default */
        $orderId = new OrderId(value: 'ord-strict-1');

        /** @When reconstituting the Order strictly with no state */
        $order = Order::reconstituteStrict(
            identity: $orderId,
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 1)
        );

        /** @Then the defaulted status does not trigger an incomplete-state failure */
        self::assertTrue($order->identity()->equals(other: $orderId));
    }

    public function testReconstituteStrictWhenMultipleRequiredPropertiesMissingThenNamesThemAll(): void
    {
        /** @Given an identity for an aggregate carrying two required properties */
        $reservationId = new ReservationId(value: 'res-multi');

        try {
            /** @When reconstituting strictly with both required properties omitted */
            GuestReservation::reconstituteStrict(
                identity: $reservationId,
                aggregateState: [],
                aggregateVersion: AggregateVersion::of(value: 1)
            );
        } catch (IncompleteAggregateState $exception) {
            /** @Then the exception names every uninitialized required property */
            self::assertEqualsCanonicalizing(['status', 'guest'], $exception->propertyNames);
        }
    }
}
