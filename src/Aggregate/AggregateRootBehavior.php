<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\EntityBehavior;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Internal\AggregateReflection;
use TinyBlocks\BuildingBlocks\Internal\ClassName;

trait AggregateRootBehavior
{
    use EntityBehavior;

    private EventRecords $recordedEvents;

    private AggregateVersion $aggregateVersion;

    protected static function createBlank(Identity $identity): static
    {
        $aggregate = AggregateReflection::instantiate(class: static::class);
        AggregateReflection::assignProperty(target: $aggregate, property: $aggregate->identityName(), value: $identity);
        $aggregate->recordedEvents = EventRecords::createFromEmpty();

        return $aggregate;
    }

    public function modelVersion(): ModelVersion
    {
        return ModelVersion::initial();
    }

    public function aggregateType(): string
    {
        return ClassName::shortName(target: static::class);
    }

    public function aggregateVersion(): AggregateVersion
    {
        return $this->aggregateVersion ?? AggregateVersion::initial();
    }

    public function peekEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

    public function pullEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();
        $this->recordedEvents = EventRecords::createFromEmpty();

        return $records;
    }

    private function nextAggregateVersion(): void
    {
        $this->aggregateVersion = $this->aggregateVersion()->next();
    }

    private function appendRecordedEvent(EventRecord $record): void
    {
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())->add(elements: $record);
    }

    private function buildEventRecord(DomainEvent $event): EventRecord
    {
        return EventRecord::from(
            event: $event,
            aggregateId: $this->identity(),
            aggregateType: $this->aggregateType(),
            aggregateVersion: $this->aggregateVersion()
        );
    }
}
