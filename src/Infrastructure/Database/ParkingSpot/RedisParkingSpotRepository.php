<?php

namespace App\Infrastructure\Database\ParkingSpot;

use App\Domain\Models\ParkingSpot;
use App\Domain\Repositories\ParkingSpotRepositoryInterface;
use App\Domain\Enums\SpotType;

readonly class RedisParkingSpotRepository implements ParkingSpotRepositoryInterface
{
    public function __construct(
        public ParkingSpotRepositoryInterface $baseRepository,
        public \Redis $redis
    ) {
    }

    public function findAll(): array
    {
        $hashKey = "parkingspots";

        $cachedParkingSpots = $this->redis->hGetAll($hashKey);

        if ($cachedParkingSpots) {
            $spots = [];

            foreach ($cachedParkingSpots as $parkingSpot) {
                $parkingSpot = json_decode($parkingSpot, true);
                $spots[] = new ParkingSpot(
                    $parkingSpot['id'],
                    $parkingSpot['spotNumber'],
                    $parkingSpot['floorNumber'],
                    SpotType::from($parkingSpot['spotType'])
                );
            }

            return $spots;
        }

        $spots = $this->baseRepository->findAll();

        if (!empty($spots)) {
            $hashData = [];
            foreach ($spots as $spot) {
                $hashData[$spot->id] = json_encode($spot);
            }

            $this->redis->hMSet($hashKey, $hashData);

            $this->redis->expire($hashKey, 3600);
        }

        return $spots;
    }
}
