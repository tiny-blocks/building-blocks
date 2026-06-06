<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

final class SpecializedReservation extends BaseReservation
{
    public static function book(ReservationId $id): SpecializedReservation
    {
        $reservation = new SpecializedReservation(id: $id);
        $reservation->pushEvent(event: new ReservationBooked());

        return $reservation;
    }

    public function confirm(): void
    {
        $this->pushEvent(event: new ReservationConfirmed());
    }
}
