<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecords;

trait EventualAggregateRootBehavior
{
    use AggregateRootBehavior;

    protected function push(DomainEvent $event): void
    {
        $this->nextSequenceNumber();
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())
            ->add(elements: $this->buildEventRecord(event: $event));
    }
}
