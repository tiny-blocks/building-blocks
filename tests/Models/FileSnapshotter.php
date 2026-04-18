<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshotter;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotterBehavior;

final class FileSnapshotter implements Snapshotter
{
    use SnapshotterBehavior;

    private ?Snapshot $latest = null;

    public function lastSnapshot(): ?Snapshot
    {
        return $this->latest;
    }

    protected function persist(Snapshot $snapshot): void
    {
        $this->latest = $snapshot;
    }
}
