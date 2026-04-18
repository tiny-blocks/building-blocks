<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;

/**
 * Port for capturing and persisting aggregate snapshots.
 *
 * <p>Infrastructure adapters implement this interface to store snapshots in whatever backend is
 * appropriate: a relational database table keyed by aggregate id, a document store, object storage, or
 * even an in-process cache. The domain layer depends only on this contract and remains unaware of the
 * underlying mechanism.</p>
 *
 * <p>The shipped {@see SnapshotterBehavior} trait captures the snapshot via {@see Snapshot::fromAggregate}
 * and delegates the storage step to a concrete <code>persist()</code> hook, leaving adapters with only
 * the storage concern to implement.</p>
 */
interface Snapshotter
{
    /**
     * Captures and persists a snapshot of the given aggregate.
     *
     * @param EventSourcingRoot $aggregate The aggregate to snapshot.
     */
    public function take(EventSourcingRoot $aggregate): void;
}
