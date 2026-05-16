<?php

namespace App\UI\Controllers\ParkingSpot;

use App\Domain\Repositories\ParkingSpotRepositoryInterface;

readonly class ParkingSpotController
{
    public function __construct(
        private ParkingSpotRepositoryInterface $parkingSpotRepository
    ) {
    }

    public function index(): void
    {
        $spots = $this->parkingSpotRepository->findAll();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $spots
        ]);
    }
}