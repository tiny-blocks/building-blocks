<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Event\SnapshotData;
use TinyBlocks\Time\Instant;

final class EventRecordTest extends TestCase
{
    public function testEventRecordExposesEveryConstructorField(): void
    {
        /** @Given every required field for an EventRecord */
        $id = Uuid::uuid4();
        $orderId = new OrderId(value: 'ord-1');
        $placedEvent = new OrderPlaced(item: 'book');
        $eventType = EventType::fromString(value: 'OrderPlaced');
        $revision = Revision::initial();
        $occurredOn = Instant::now();
        $snapshotData = new SnapshotData(payload: ['status' => 'placed']);
        $sequenceNumber = SequenceNumber::first();

        /** @When constructing the EventRecord */
        $record = new EventRecord(
            id: $id,
            type: $eventType,
            event: $placedEvent,
            identity: $orderId,
            revision: $revision,
            occurredOn: $occurredOn,
            snapshotData: $snapshotData,
            aggregateType: 'Order',
            sequenceNumber: $sequenceNumber
        );

        /** @Then each public field is accessible with the expected value */
        self::assertSame($id, $record->id);
        self::assertSame($eventType, $record->type);
        self::assertSame($placedEvent, $record->event);
        self::assertSame($orderId, $record->identity);
        self::assertSame($revision, $record->revision);
        self::assertSame($occurredOn, $record->occurredOn);
        self::assertSame($snapshotData, $record->snapshotData);
        self::assertSame('Order', $record->aggregateType);
        self::assertSame($sequenceNumber, $record->sequenceNumber);
    }

    public function testEqualsReturnsTrueForRecordsBuiltFromEqualValues(): void
    {
        /** @Given shared values for two EventRecord instances */
        $id = Uuid::uuid4();
        $orderId = new OrderId(value: 'ord-1');
        $placedEvent = new OrderPlaced(item: 'book');
        $eventType = EventType::fromString(value: 'OrderPlaced');
        $revision = Revision::initial();
        $occurredOn = Instant::now();
        $snapshotData = new SnapshotData(payload: []);
        $sequenceNumber = SequenceNumber::first();

        /** @And two records constructed from those identical values */
        $first = new EventRecord(
            id: $id,
            type: $eventType,
            event: $placedEvent,
            identity: $orderId,
            revision: $revision,
            occurredOn: $occurredOn,
            snapshotData: $snapshotData,
            aggregateType: 'Order',
            sequenceNumber: $sequenceNumber
        );
        $second = new EventRecord(
            id: $id,
            type: $eventType,
            event: $placedEvent,
            identity: $orderId,
            revision: $revision,
            occurredOn: $occurredOn,
            snapshotData: $snapshotData,
            aggregateType: 'Order',
            sequenceNumber: $sequenceNumber
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForRecordsWithDifferentIdentifiers(): void
    {
        /** @Given shared values except the identifier */
        $orderId = new OrderId(value: 'ord-1');
        $placedEvent = new OrderPlaced(item: 'book');
        $eventType = EventType::fromString(value: 'OrderPlaced');
        $revision = Revision::initial();
        $occurredOn = Instant::now();
        $snapshotData = new SnapshotData(payload: []);
        $sequenceNumber = SequenceNumber::first();

        /** @And two records with different UUIDs */
        $first = new EventRecord(
            id: Uuid::uuid4(),
            type: $eventType,
            event: $placedEvent,
            identity: $orderId,
            revision: $revision,
            occurredOn: $occurredOn,
            snapshotData: $snapshotData,
            aggregateType: 'Order',
            sequenceNumber: $sequenceNumber
        );
        $second = new EventRecord(
            id: Uuid::uuid4(),
            type: $eventType,
            event: $placedEvent,
            identity: $orderId,
            revision: $revision,
            occurredOn: $occurredOn,
            snapshotData: $snapshotData,
            aggregateType: 'Order',
            sequenceNumber: $sequenceNumber
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }
}
