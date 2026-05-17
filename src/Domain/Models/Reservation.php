<?php

namespace App\Domain\Models;

use App\Domain\Enums\BookingStatus;

readonly class Reservation implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public int $parkingSpotId,
        public int $userId,
        public string $startTime,
        public string $endTime,
        public string $createdAt,
        public BookingStatus $bookingStatus = BookingStatus::BOOKED,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'parking_spot_id' => $this->parkingSpotId,
            'user_id' => $this->userId,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => $this->bookingStatus->value,
            'created_at' => $this->createdAt
        ];
    }
}
