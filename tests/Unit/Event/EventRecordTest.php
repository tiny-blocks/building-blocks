<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Utc;
use TinyBlocks\BuildingBlocks\Uuid;

final class EventRecordTest extends TestCase
{
    public function testEventRecordExposesEveryField(): void
    {
        /** @Given an event identifier */
        $id = Uuid::generateV7();

        /** @And an aggregate identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @And a domain event */
        $placedEvent = new OrderPlaced(item: 'book');

        /** @And the occurrence timestamp */
        $occurredAt = Utc::now();

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @When building the EventRecord via the factory */
        $record = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: $id,
            occurredAt: $occurredAt
        );

        /** @Then each public field is accessible with the expected value */
        self::assertSame($id, $record->id);
        self::assertSame($placedEvent, $record->event);
        self::assertSame($orderId, $record->aggregateId);
        self::assertSame(1, $record->revision->value);
        self::assertSame($occurredAt, $record->occurredAt);
        self::assertSame('Order', $record->aggregateType);
        self::assertSame('OrderPlaced', $record->eventType->value);
        self::assertSame($aggregateVersion, $record->aggregateVersion);
    }

    public function testEqualsReturnsTrueForRecordsBuiltFromEqualValues(): void
    {
        /** @Given an event identifier */
        $id = Uuid::generateV7();

        /** @And an aggregate identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @And a domain event */
        $placedEvent = new OrderPlaced(item: 'book');

        /** @And the occurrence timestamp */
        $occurredAt = Utc::now();

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @And a first record built from those values */
        $first = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: $id,
            occurredAt: $occurredAt
        );

        /** @And a second record built from the same values */
        $second = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: $id,
            occurredAt: $occurredAt
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForRecordsWithDifferentIdentifiers(): void
    {
        /** @Given an aggregate identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @And a domain event */
        $placedEvent = new OrderPlaced(item: 'book');

        /** @And the occurrence timestamp */
        $occurredAt = Utc::now();

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @And a first record with a unique identifier */
        $first = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: Uuid::generateV7(),
            occurredAt: $occurredAt
        );

        /** @And a second record with a different identifier */
        $second = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: Uuid::generateV7(),
            occurredAt: $occurredAt
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testFromFactoryBuildsRecordWithRequiredFields(): void
    {
        /** @Given an aggregate identity */
        $orderId = new OrderId(value: 'ord-of-1');

        /** @And a domain event */
        $placedEvent = new OrderPlaced(item: 'notebook');

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @When building the record via the factory */
        $record = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion
        );

        /** @Then the envelope carries the expected metadata */
        self::assertSame('OrderPlaced', $record->eventType->value);
        self::assertSame(1, $record->revision->value);
        self::assertSame($placedEvent, $record->event);
        self::assertSame($orderId, $record->aggregateId);
        self::assertSame('Order', $record->aggregateType);
        self::assertSame($aggregateVersion, $record->aggregateVersion);
    }

    public function testFromFactoryUsesProvidedOptionalFields(): void
    {
        /** @Given an explicit identifier */
        $id = Uuid::generateV7();

        /** @And an aggregate identity */
        $orderId = new OrderId(value: 'ord-of-2');

        /** @And a domain event */
        $placedEvent = new OrderPlaced(item: 'pen');

        /** @And an explicit occurrence timestamp */
        $occurredAt = Utc::now();

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @When building the record via the factory with all optional fields */
        $record = EventRecord::from(
            event: $placedEvent,
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: $id,
            occurredAt: $occurredAt
        );

        /** @Then the optional fields are applied exactly */
        self::assertSame($id, $record->id);
        self::assertSame($occurredAt, $record->occurredAt);
    }
}
