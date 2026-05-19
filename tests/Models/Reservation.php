<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use RuntimeException;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

final class Reservation implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private string $status;

    private function __construct(private readonly ReservationId $id, string $status)
    {
        $this->status = $status;
    }

    public static function book(ReservationId $id): Reservation
    {
        $reservation = new Reservation(id: $id, status: 'pending');
        $reservation->push(event: new ReservationBooked());

        return $reservation;
    }

    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            $template = 'Cannot confirm reservation in status <%s>.';

            throw new RuntimeException(message: sprintf($template, $this->status));
        }

        $this->status = 'confirmed';
        $this->push(event: new ReservationConfirmed());
    }
}
