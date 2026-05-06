<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidSnapshotCount;

final readonly class SnapshotEvery implements SnapshotCondition
{
    private function __construct(private int $count)
    {
        if ($count < 1) {
            throw new InvalidSnapshotCount(count: $count);
        }
    }

    public static function events(int $count): SnapshotEvery
    {
        return new SnapshotEvery(count: $count);
    }

    public function shouldSnapshot(EventSourcingRoot $aggregate): bool
    {
        $value = $aggregate->getSequenceNumber()->value;

        return $value > 0 && $value % $this->count === 0;
    }
}
