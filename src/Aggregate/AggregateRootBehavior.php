<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use Ramsey\Uuid\Uuid;
use ReflectionClass;
use TinyBlocks\BuildingBlocks\Entity\EntityBehavior;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Event\SnapshotData;
use TinyBlocks\Time\Instant;

trait AggregateRootBehavior
{
    use EntityBehavior;

    private EventRecords $recordedEvents;

    private SequenceNumber $sequenceNumber;

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber ?? SequenceNumber::initial();
    }

    public function getModelVersion(): SequenceNumber
    {
        return SequenceNumber::of(value: $this->modelVersion());
    }

    public function buildAggregateName(): string
    {
        return new ReflectionClass(static::class)->getShortName();
    }

    protected function modelVersion(): int
    {
        return 0;
    }

    protected function nextSequenceNumber(): void
    {
        $this->sequenceNumber = $this->getSequenceNumber()->next();
    }

    public function recordedEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

    protected function generateSnapshotData(): SnapshotData
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents']);

        return new SnapshotData(payload: $state);
    }

    protected function buildEventRecord(DomainEvent $event): EventRecord
    {
        return new EventRecord(
            id: Uuid::uuid4(),
            type: EventType::fromEvent(event: $event),
            event: $event,
            identity: $this->getIdentity(),
            revision: $event->revision(),
            occurredOn: Instant::now(),
            snapshotData: $this->generateSnapshotData(),
            aggregateType: $this->buildAggregateName(),
            sequenceNumber: $this->getSequenceNumber()
        );
    }
}
