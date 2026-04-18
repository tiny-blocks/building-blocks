<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Event\SnapshotData;
use TinyBlocks\Time\Instant;

final class EventRecordsTest extends TestCase
{
    public function testCreateFromEmptyYieldsAnEmptyCollection(): void
    {
        /** @Given an empty EventRecords collection */
        $records = EventRecords::createFromEmpty();

        /** @When checking whether it is empty */
        $result = $records->isEmpty();

        /** @Then the collection is empty */
        self::assertTrue($result);
    }

    public function testAddingARecordYieldsACollectionOfOneElement(): void
    {
        /** @Given an empty EventRecords collection */
        $records = EventRecords::createFromEmpty();

        /** @And a freshly built event record */
        $record = new EventRecord(
            id: Uuid::uuid4(),
            type: EventType::fromString(value: 'OrderPlaced'),
            event: new OrderPlaced(item: 'book'),
            identity: new OrderId(value: 'ord-1'),
            revision: new Revision(value: 1),
            occurredOn: Instant::now(),
            snapshotData: new SnapshotData(data: []),
            aggregateType: 'Order',
            sequenceNumber: new SequenceNumber(value: 1)
        );

        /** @When adding the record */
        $updated = $records->add($record);

        /** @Then the count is one */
        self::assertSame(1, $updated->count());
    }

    public function testFirstElementRoundTripsTheAddedRecord(): void
    {
        /** @Given a record added to an empty EventRecords collection */
        $record = new EventRecord(
            id: Uuid::uuid4(),
            type: EventType::fromString(value: 'OrderPlaced'),
            event: new OrderPlaced(item: 'book'),
            identity: new OrderId(value: 'ord-1'),
            revision: new Revision(value: 1),
            occurredOn: Instant::now(),
            snapshotData: new SnapshotData(data: []),
            aggregateType: 'Order',
            sequenceNumber: new SequenceNumber(value: 1)
        );
        $records = EventRecords::createFromEmpty()->add($record);

        /** @When retrieving the first element */
        $first = $records->first();

        /** @Then it matches the record added */
        self::assertSame($record, $first);
    }
}
