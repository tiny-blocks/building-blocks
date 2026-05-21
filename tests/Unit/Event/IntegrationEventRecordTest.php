<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmed;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmedV2;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventRecord;
use TinyBlocks\Time\Instant;

final class IntegrationEventRecordTest extends TestCase
{
    public function testFromReusesOriginatingRecordMetadataAndCarriesIntegrationEvent(): void
    {
        /** @Given an explicit event identifier */
        $id = Uuid::uuid4();

        /** @And an aggregate identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @And an explicit occurrence timestamp */
        $occurredAt = Instant::now();

        /** @And the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @And an event record built with explicit metadata */
        $eventRecord = EventRecord::of(
            event: new OrderPlaced(item: 'book'),
            aggregateId: $orderId,
            aggregateType: 'Order',
            aggregateVersion: $aggregateVersion,
            id: $id,
            occurredAt: $occurredAt
        );

        /** @And a PaymentConfirmed integration event */
        $integrationEvent = new PaymentConfirmed(orderId: 'ord-1');

        /** @When building the integration event record */
        $record = IntegrationEventRecord::from(
            eventRecord: $eventRecord,
            integrationEvent: $integrationEvent
        );

        /** @Then the transport metadata is reused from the originating record */
        self::assertSame($id, $record->id);
        self::assertSame($occurredAt, $record->occurredAt);
        self::assertSame($orderId, $record->aggregateId);
        self::assertSame('Order', $record->aggregateType);
        self::assertSame($aggregateVersion, $record->aggregateVersion);

        /** @And the envelope carries the integration event */
        self::assertSame($integrationEvent, $record->event);
    }

    public function testFromDerivesRevisionFromIntegrationEvent(): void
    {
        /** @Given an OrderPlaced domain event with the initial revision */
        $domainEvent = new OrderPlaced(item: 'notebook');

        /** @And an event record wrapping the domain event */
        $eventRecord = EventRecord::of(
            event: $domainEvent,
            aggregateId: new OrderId(value: 'ord-2'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And a PaymentConfirmedV2 integration event with revision 2 */
        $integrationEvent = new PaymentConfirmedV2(orderId: 'ord-2');

        /** @When building the integration event record */
        $record = IntegrationEventRecord::from(
            eventRecord: $eventRecord,
            integrationEvent: $integrationEvent
        );

        /** @Then the revision is taken from the integration event, not from the domain event */
        self::assertSame(2, $record->revision->value);
    }

    public function testFromDerivesEventTypeFromIntegrationEventClassName(): void
    {
        /** @Given an event record wrapping an OrderPlaced domain event */
        $eventRecord = EventRecord::of(
            event: new OrderPlaced(item: 'pen'),
            aggregateId: new OrderId(value: 'ord-3'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And a PaymentConfirmed integration event */
        $integrationEvent = new PaymentConfirmed(orderId: 'ord-3');

        /** @When building the integration event record */
        $record = IntegrationEventRecord::from(
            eventRecord: $eventRecord,
            integrationEvent: $integrationEvent
        );

        /** @Then the event type reflects the integration event class, not the domain event class */
        self::assertSame('PaymentConfirmed', $record->eventType->value);
    }
}
