<?php

namespace App\UI\Controllers\ParkingSpot;

use App\Domain\Repositories\ParkingSpotRepositoryInterface;
use App\UI\Traits\RequireAuth;

readonly class ParkingSpotController
{
    use RequireAuth;

    public function __construct(
        private ParkingSpotRepositoryInterface $parkingSpotRepository
    ) {
    }

    public function index(): void
    {
        try {
            $this->authenticate();
        } catch(\Exception $e) {
            http_response_code($e->getCode());
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        $spots = $this->parkingSpotRepository->findAll();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $spots
        ]);
    }
}