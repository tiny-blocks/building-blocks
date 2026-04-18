<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
use TinyBlocks\BuildingBlocks\Event\Revision;

final class OrderWithoutIdentityConstant implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    public function ship(): void
    {
        $this->pushEvent(event: new OrderShipped(carrier: 'DHL'), revision: new Revision(value: 1));
    }
}
