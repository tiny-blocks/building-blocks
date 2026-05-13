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
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotData;
use TinyBlocks\Time\Instant;

trait AggregateRootBehavior
{
    use EntityBehavior;

    private EventRecords $recordedEvents;

    private SequenceNumber $sequenceNumber;

    public function sequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber ?? SequenceNumber::initial();
    }

    public function modelVersion(): ModelVersion
    {
        return ModelVersion::initial();
    }

    public function aggregateName(): string
    {
        return new ReflectionClass(objectOrClass: static::class)->getShortName();
    }

    protected function nextSequenceNumber(): void
    {
        $this->sequenceNumber = $this->sequenceNumber()->next();
    }

    protected function generateSnapshotData(): SnapshotData
    {
        return new SnapshotData(payload: $this->snapshotState());
    }

    protected function reconstituteSequenceNumber(SequenceNumber $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }

    protected function snapshotState(): array
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents'], $state['sequenceNumber']);

        return $state;
    }

    public function recordedEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

    protected function buildEventRecord(DomainEvent $event): EventRecord
    {
        return new EventRecord(
            id: Uuid::uuid4(),
            type: EventType::fromEvent(event: $event),
            event: $event,
            identity: $this->identity(),
            revision: $event->revision(),
            occurredOn: Instant::now(),
            snapshotData: $this->generateSnapshotData(),
            aggregateType: $this->aggregateName(),
            sequenceNumber: $this->sequenceNumber()
        );
    }
}
