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

    /**
     * Persists the given snapshot.
     *
     * <p>Implemented by the consumer. Invoked once per call to {@see take()} with the snapshot already
     * captured from the aggregate. Storage format and location are entirely up to the implementation.
     * This hook simply hands over the captured snapshot.</p>
     *
     * @param Snapshot $snapshot The snapshot to persist.
     */
    abstract protected function persist(Snapshot $snapshot): void;
}
