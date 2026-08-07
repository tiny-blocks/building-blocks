<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Utc;
use TinyBlocks\BuildingBlocks\Uuid;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class EventRecord implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(
        public Uuid $id,
        public DomainEvent $event,
        public Revision $revision,
        public EventType $eventType,
        public Utc $occurredAt,
        public Identity $aggregateId,
        public string $aggregateType,
        public AggregateVersion $aggregateVersion
    ) {
    }

    /**
     * Creates an EventRecord from a domain event and its required envelope fields.
     *
     * @param DomainEvent $event The event being recorded.
     * @param Identity $aggregateId The aggregate identity that produced the event.
     * @param string $aggregateType The short class name of the aggregate.
     * @param AggregateVersion $aggregateVersion The aggregate version assigned to this envelope.
     * @param Uuid|null $id Optional explicit identifier. Defaults to a fresh UUIDv7.
     * @param Utc|null $occurredAt Optional explicit occurrence timestamp. Defaults to now.
     * @return EventRecord The constructed envelope.
     */
    public static function from(
        DomainEvent $event,
        Identity $aggregateId,
        string $aggregateType,
        AggregateVersion $aggregateVersion,
        ?Uuid $id = null,
        ?Utc $occurredAt = null
    ): EventRecord {
        return new EventRecord(
            id: ($id ?? Uuid::generateV7()),
            event: $event,
            revision: $event->revision(),
            eventType: EventType::fromDomainEvent(event: $event),
            occurredAt: ($occurredAt ?? Utc::now()),
            aggregateId: $aggregateId,
            aggregateType: $aggregateType,
            aggregateVersion: $aggregateVersion
        );
    }
}
