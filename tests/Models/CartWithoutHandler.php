<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

final class CartWithoutHandler implements EventSourcingRoot
{
    use EventSourcingRootBehavior;

    private CartId $cartId;

    public function applySnapshot(Snapshot $snapshot): void
    {
    }

    protected function identityProperty(): string
    {
        return 'cartId';
    }

    public function modelVersion(): ModelVersion
    {
        return ModelVersion::of(value: 1);
    }
}
