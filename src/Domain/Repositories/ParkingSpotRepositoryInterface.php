<?php

namespace App\Domain\Repositories;

interface ParkingSpotRepositoryInterface
{
    public function findAll(): array;
}
