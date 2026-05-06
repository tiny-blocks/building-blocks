<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;

final readonly class SnapshotNever implements SnapshotCondition
{
    private function __construct()
    {
    }

    public static function create(): SnapshotNever
    {
        return new SnapshotNever();
    }

    public function shouldSnapshot(EventSourcingRoot $aggregate): bool
    {
        return false;
    }
}
