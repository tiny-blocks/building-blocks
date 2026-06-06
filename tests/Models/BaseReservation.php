<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

class BaseReservation implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    protected function __construct(protected readonly ReservationId $id)
    {
    }
}
