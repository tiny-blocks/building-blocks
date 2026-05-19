<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;

/**
 * Strategy that decides when a snapshot of an event-sourced aggregate should be taken.
 *
 * <p>Typical implementations check the aggregate's version against a threshold (for example, take a
 * snapshot every <em>N</em> events) or combine version checks with a time-based policy. Keeping the
 * decision behind a strategy lets consumers mix and match policies per aggregate type without branching
 * inside the snapshotter.</p>
 */
interface SnapshotCondition
{
    /**
     * Tells whether a snapshot of the given aggregate should be taken now.
     *
     * @param EventSourcingRoot $aggregate The aggregate under evaluation.
     * @return bool True when a snapshot should be taken.
     */
    public function shouldSnapshot(EventSourcingRoot $aggregate): bool;
}
