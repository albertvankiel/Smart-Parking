<?php

namespace App\Infrastructure\Database\ParkingSpot;

use App\Domain\Models\ParkingSpot;
use App\Infrastructure\Database\AbstractDatabaseRepository;
use App\Domain\Repositories\ParkingSpotRepositoryInterface;
use App\Domain\Enums\SpotType;

readonly class MySQLParkingSpotRepository extends AbstractDatabaseRepository implements ParkingSpotRepositoryInterface
{
    public function findAll(): array
    {
        // @TODO Order by floor number and spot number in query
        $stmnt = $this->pdo->prepare("SELECT * FROM parking_spots");
        $stmnt->execute();

        $rows = $stmnt->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];

        foreach ($rows as $row) {
            $data[] = new ParkingSpot(
                $row['id'], 
                $row['spot_number'], 
                $row['floor_number'], 
                SpotType::from($row['spot_type'])
            );
        }

        return $data;
    }
}