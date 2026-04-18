<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotCondition;

final class EveryTwoEvents implements SnapshotCondition
{
    public function shouldSnapshot(EventSourcingRoot $aggregate): bool
    {
        return $aggregate->getSequenceNumber()->value % 2 === 0;
    }
}
