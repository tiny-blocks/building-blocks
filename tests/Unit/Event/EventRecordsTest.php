<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\Time\Instant;

final class EventRecordsTest extends TestCase
{
    public function testCreateFromEmptyYieldsAnEmptyCollection(): void
    {
        /** @Given an empty EventRecords collection */
        $records = EventRecords::createFromEmpty();

        /** @When checking whether it is empty */
        $isEmpty = $records->isEmpty();

        /** @Then the collection is empty */
        self::assertTrue($isEmpty);
    }

    public function testAddingARecordYieldsACollectionOfOneElement(): void
    {
        /** @Given an empty EventRecords collection */
        $records = EventRecords::createFromEmpty();

        /** @And a freshly built event record */
        $record = new EventRecord(
            id: Uuid::uuid4(),
            event: new OrderPlaced(item: 'book'),
            revision: Revision::initial(),
            eventType: EventType::fromString(value: 'OrderPlaced'),
            occurredAt: Instant::now(),
            aggregateId: new OrderId(value: 'ord-1'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @When adding the record */
        $updated = $records->add($record);

        /** @Then the count is one */
        self::assertSame(1, $updated->count());
    }

    public function testFirstElementRoundTripsTheAddedRecord(): void
    {
        /** @Given a freshly built event record */
        $record = new EventRecord(
            id: Uuid::uuid4(),
            event: new OrderPlaced(item: 'book'),
            revision: Revision::initial(),
            eventType: EventType::fromString(value: 'OrderPlaced'),
            occurredAt: Instant::now(),
            aggregateId: new OrderId(value: 'ord-1'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And a collection carrying that record */
        $records = EventRecords::createFromEmpty()->add($record);

        /** @When retrieving the first element */
        $first = $records->first();

        /** @Then it matches the record added */
        self::assertSame($record, $first);
    }
}
