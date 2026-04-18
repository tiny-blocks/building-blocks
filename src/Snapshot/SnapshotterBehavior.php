<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;

trait SnapshotterBehavior
{
    public function take(EventSourcingRoot $aggregate): void
    {
        $this->persist(snapshot: Snapshot::fromAggregate(aggregate: $aggregate));
    }

    abstract protected function persist(Snapshot $snapshot): void;
}
