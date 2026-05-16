<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Reservation;

interface ReservationRepositoryInterface
{
    /**
     * Attempt to book a parking spot.
     * Must handle concurrency to prevent double booking.
     * 
     * @throws \Exception If the spot is already booked or does not exist.
     */
    public function bookSpot(int $userId, int $parkingSpotId, string $startTime, string $endTime): Reservation;
}