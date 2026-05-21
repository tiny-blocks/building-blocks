<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class EventRecord implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(
        public UuidInterface $id,
        public DomainEvent $event,
        public Revision $revision,
        public EventType $eventType,
        public Instant $occurredAt,
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
     * @param UuidInterface|null $id Optional explicit identifier. Defaults to a fresh UUIDv4.
     * @param Instant|null $occurredAt Optional explicit occurrence timestamp. Defaults to now.
     * @return EventRecord The constructed envelope.
     */
    public static function of(
        DomainEvent $event,
        Identity $aggregateId,
        string $aggregateType,
        AggregateVersion $aggregateVersion,
        ?UuidInterface $id = null,
        ?Instant $occurredAt = null
    ): EventRecord {
        return new EventRecord(
            id: $id ?? Uuid::uuid4(),
            event: $event,
            revision: $event->revision(),
            eventType: EventType::fromDomainEvent(event: $event),
            occurredAt: $occurredAt ?? Instant::now(),
            aggregateId: $aggregateId,
            aggregateType: $aggregateType,
            aggregateVersion: $aggregateVersion
        );
    }
}
