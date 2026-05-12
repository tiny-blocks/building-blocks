<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotData;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class EventRecord implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(
        public UuidInterface $id,
        public EventType $type,
        public DomainEvent $event,
        public Identity $identity,
        public Revision $revision,
        public Instant $occurredOn,
        public SnapshotData $snapshotData,
        public string $aggregateType,
        public SequenceNumber $sequenceNumber
    ) {
    }

    public static function of(
        DomainEvent $event,
        Identity $identity,
        string $aggregateType,
        SequenceNumber $sequenceNumber,
        ?UuidInterface $id = null,
        ?Instant $occurredOn = null,
        ?SnapshotData $snapshotData = null
    ): EventRecord {
        return new EventRecord(
            id: $id ?? Uuid::uuid4(),
            type: EventType::fromEvent(event: $event),
            event: $event,
            identity: $identity,
            revision: $event->revision(),
            occurredOn: $occurredOn ?? Instant::now(),
            snapshotData: $snapshotData ?? new SnapshotData(payload: []),
            aggregateType: $aggregateType,
            sequenceNumber: $sequenceNumber
        );
    }
}
