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

    /**
     * Find all reservatiosn for a specific date.
     * 
     * @param string $date The date in YYYY-MM-DD format
     * @return Reservation[]
     */
    public function findByDate(string $date): array;

    /**
     * Mark reservation as complete.
     * 
     * @param int $id ID of the reservation.
     * @return bool True if successful.
     */
    public function complete(int $id): bool;
}