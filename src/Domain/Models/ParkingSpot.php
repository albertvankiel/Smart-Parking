<?php

namespace App\Domain\Models;

use App\Domain\Enums\SpotType;

readonly class ParkingSpot
{
    public function __construct(
        public int $id,
        public string $spotNumber,
        public int $floorNumber,
        public SpotType $spotType
    ) {
    }
}