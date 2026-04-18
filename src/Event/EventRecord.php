<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use Ramsey\Uuid\UuidInterface;
use TinyBlocks\BuildingBlocks\Entity\Identity;
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
}
