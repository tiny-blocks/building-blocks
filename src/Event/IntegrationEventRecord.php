<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Utc;
use TinyBlocks\BuildingBlocks\Uuid;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

/**
 * Envelope for an {@see IntegrationEvent} ready to be persisted to the outbox.
 *
 * <p>Produced by infrastructure adapters after an {@see IntegrationEventTranslator}
 * converts a {@see DomainEvent} carried inside an {@see EventRecord} into an
 * {@see IntegrationEvent}. The envelope keeps the transport metadata of the originating
 * record (identifier, occurrence timestamp, aggregate identity, aggregate type, and
 * aggregate version) while replacing the payload with the public-contract event and
 * deriving its own {@see Revision} and {@see EventType} from the integration event.</p>
 *
 * <p>The identifier is reused from the originating {@see EventRecord} so that retries
 * by the outbox relay remain idempotent end-to-end: external consumers see a stable
 * event id even when the relay republishes after a transient failure.</p>
 *
 * <p>Instances are only constructed via {@see IntegrationEventRecord::from}.</p>
 */
final readonly class IntegrationEventRecord implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(
        public Uuid $id,
        public IntegrationEvent $event,
        public Revision $revision,
        public EventType $eventType,
        public Utc $occurredAt,
        public Identity $aggregateId,
        public string $aggregateType,
        public AggregateVersion $aggregateVersion
    ) {
    }

    /**
     * Builds an integration event envelope from the originating event record and the
     * translated integration event.
     *
     * @param EventRecord $eventRecord The originating domain event record. Supplies the
     *                                 identifier, occurrence timestamp, aggregate id,
     *                                 aggregate type, and aggregate version.
     * @param IntegrationEvent $integrationEvent The integration event produced by the
     *                                           translator. Supplies the payload, the
     *                                           {@see Revision}, and the {@see EventType}.
     * @return IntegrationEventRecord The constructed envelope.
     */
    public static function from(
        EventRecord $eventRecord,
        IntegrationEvent $integrationEvent
    ): IntegrationEventRecord {
        return new IntegrationEventRecord(
            id: $eventRecord->id,
            event: $integrationEvent,
            revision: $integrationEvent->revision(),
            eventType: EventType::fromIntegrationEvent(event: $integrationEvent),
            occurredAt: $eventRecord->occurredAt,
            aggregateId: $eventRecord->aggregateId,
            aggregateType: $eventRecord->aggregateType,
            aggregateVersion: $eventRecord->aggregateVersion
        );
    }
}
