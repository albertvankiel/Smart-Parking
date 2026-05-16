<?php

/**
 * This script runs as a continuous daemon process in the background.
 * It is responsible for periodically polling the database to identify
 * and release parking spot reservations that have expired (end_time < NOW())
 * but are still marked as 'booked'.
 *
 * This script is intended to be run via the CLI.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\ServiceContainer;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Infrastructure\Database\Reservation\MySQLReservationRepository;

echo "Starting Background Worker...\n";

$serviceContainer = new ServiceContainer();

$serviceContainer->bind(\PDO::class, function () {
    $host = getenv('MYSQL_HOST');
    $db = getenv('MYSQL_DB');
    $user = getenv('MYSQL_USER');
    $pw = getenv('MYSQL_PASSWORD');
    return new \PDO("mysql:host={$host};dbname={$db}", $user, $pw, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]);
});

$serviceContainer->bind(ReservationRepositoryInterface::class, function ($serviceContainer) {
    $pdo = $serviceContainer->get(\PDO::class);
    return new MySQLReservationRepository($pdo);
});

$reservationRepository = $serviceContainer->get(ReservationRepositoryInterface::class);
$pdo = $serviceContainer->get(\PDO::class);

while (true) {
    try {
        $query = "
            SELECT id, parking_spot_id 
            FROM reservations 
            WHERE status = 'booked' 
              AND end_time < NOW()
        ";

        $stmt = $pdo->query($query);
        $staleReservations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($staleReservations as $row) {
            $reservationId = (int) $row['id'];
            $spotId = (int) $row['parking_spot_id'];

            $success = $reservationRepository->complete($reservationId);

            if ($success) {
                echo sprintf("[%s] Auto-released Spot #%d (Reservation ID %d)\n", date('Y-m-d H:i:s'), $spotId, $reservationId);

                $webSocketServer = getenv('WEBSOCKET_SERVER');
                if ($webSocketServer) {
                    $options = [
                        'http' => [
                            'header' => "Content-Type: application/json\r\n",
                            'method' => 'POST',
                            'content' => json_encode(['action' => 'spot_released', 'spot_id' => $spotId]),
                            'timeout' => 1
                        ]
                    ];
                    @file_get_contents($webSocketServer, false, stream_context_create($options));
                }
            }
        }
    } catch (\Exception $e) {
        echo sprintf("[%s] Worker Error: %s\n", date('Y-m-d H:i:s'), $e->getMessage());
    }

    sleep(5);
}
