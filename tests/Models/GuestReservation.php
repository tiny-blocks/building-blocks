<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

final class GuestReservation implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $guest;

    private string $status;

    public function __construct(private readonly ReservationId $id, string $guest, string $status)
    {
        $this->guest = $guest;
        $this->status = $status;
    }
}
