<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\Revision;

trait EventualAggregateRootBehavior
{
    use AggregateRootBehavior;

    private EventRecords $recordedEvents;

    public function recordedEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

    public function clearRecordedEvents(): void
    {
        $this->recordedEvents = EventRecords::createFromEmpty();
    }

    protected function push(DomainEvent $event, Revision $revision): void
    {
        $this->nextSequenceNumber();
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())
            ->add($this->buildEventRecord(event: $event, revision: $revision));
    }
}
