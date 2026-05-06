<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

final class OrderWithMissingIdentityProperty implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    public function ship(): void
    {
        $this->push(event: new OrderShipped(carrier: 'DHL'));
    }

    protected function identityName(): string
    {
        return 'nonExistentProperty';
    }
}
