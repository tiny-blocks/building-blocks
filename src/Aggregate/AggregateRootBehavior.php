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
use TinyBlocks\Time\Instant;

trait AggregateRootBehavior
{
    use EntityBehavior;

    private EventRecords $recordedEvents;

    private AggregateVersion $aggregateVersion;

    public function modelVersion(): ModelVersion
    {
        return ModelVersion::initial();
    }

    public function aggregateType(): string
    {
        return new ReflectionClass(objectOrClass: static::class)->getShortName();
    }

    public function aggregateVersion(): AggregateVersion
    {
        return $this->aggregateVersion ?? AggregateVersion::initial();
    }

    public function recordedEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

    private function nextAggregateVersion(): void
    {
        $this->aggregateVersion = $this->aggregateVersion()->next();
    }

    private function buildEventRecord(DomainEvent $event): EventRecord
    {
        return new EventRecord(
            id: Uuid::uuid4(),
            event: $event,
            revision: $event->revision(),
            eventType: EventType::fromEvent(event: $event),
            occurredAt: Instant::now(),
            aggregateId: $this->identity(),
            aggregateType: $this->aggregateType(),
            aggregateVersion: $this->aggregateVersion()
        );
    }
}
