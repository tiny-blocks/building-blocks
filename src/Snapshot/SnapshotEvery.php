<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidSnapshotCount;

final readonly class SnapshotEvery implements SnapshotCondition
{
    private function __construct(private int $count)
    {
        if ($count < 1) {
            throw new InvalidSnapshotCount(count: $count);
        }
    }

    /**
     * Creates a SnapshotEvery condition that triggers every N events.
     *
     * @param int $count The number of events between snapshots. Must be at least 1.
     * @return SnapshotEvery The created condition.
     * @throws InvalidSnapshotCount If the count is less than 1.
     */
    public static function events(int $count): SnapshotEvery
    {
        return new SnapshotEvery(count: $count);
    }

    public function shouldSnapshot(EventSourcingRoot $aggregate): bool
    {
        $value = $aggregate->aggregateVersion()->value;

        return $value > 0 && $value % $this->count === 0;
    }
}
