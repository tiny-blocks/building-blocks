<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class CartWithoutIdentityConstant implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    public function applySnapshot(Snapshot $snapshot): void
    {
    }
}
