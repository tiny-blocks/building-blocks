<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use Ramsey\Uuid\Uuid;
use ReflectionClass;
use TinyBlocks\BuildingBlocks\Entity\EntityBehavior;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Event\SnapshotData;
use TinyBlocks\Time\Instant;

trait AggregateRootBehavior
{
    use EntityBehavior;

    private SequenceNumber $sequenceNumber;

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber ?? SequenceNumber::initial();
    }

    public function getModelVersion(): SequenceNumber
    {
        if (!defined('static::MODEL_VERSION')) {
            return new SequenceNumber(value: 0);
        }

        return new SequenceNumber(value: static::MODEL_VERSION);
    }

    public function buildAggregateName(): string
    {
        return new ReflectionClass(objectOrClass: static::class)->getShortName();
    }

    protected function nextSequenceNumber(): void
    {
        $this->sequenceNumber = $this->getSequenceNumber()->next();
    }

    protected function buildEventRecord(DomainEvent $event, Revision $revision): EventRecord
    {
        return new EventRecord(
            id: Uuid::uuid4(),
            type: EventType::fromEvent(event: $event),
            event: $event,
            identity: $this->getIdentity(),
            revision: $revision,
            occurredOn: Instant::now(),
            snapshotData: $this->generateSnapshotData(),
            aggregateType: $this->buildAggregateName(),
            sequenceNumber: $this->getSequenceNumber()
        );
    }

    protected function generateSnapshotData(): SnapshotData
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents']);

        return new SnapshotData(data: $state);
    }
}
