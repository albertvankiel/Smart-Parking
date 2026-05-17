<?php

namespace App\UI\Controllers\Reservation;

use App\Domain\Repositories\ReservationRepositoryInterface;
use App\UI\Traits\RequireAuth;

readonly class ReservationController
{
    use RequireAuth;

    private int $userId;

    public function __construct(
        private ReservationRepositoryInterface $reservationRepository
    ) {
        $this->userId = $this->authenticate();
    }

    public function store(): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $spotId = $data['spot_id'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;

        if (!$spotId || !$startTime || !$endTime) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }

        try {
            $reservation = $this->reservationRepository->bookSpot($this->userId, $spotId, $startTime, $endTime);

            $webSocketServer = getenv('WEBSOCKET_SERVER');

            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'POST',
                    'content' => json_encode(['reservation' => $reservation]),
                    'timeout' => 1
                ]
            ];

            $context = stream_context_create($options);

            // Use @ to suppress warnings if the websockets server is offline
            @file_get_contents($webSocketServer, false, $context);

            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $reservation
            ]);
        } catch (\Exception $e) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function index(): void
    {
        $date = $_GET['date'] ?? date('Y-m-d');

        try {
            $reservations = $this->reservationRepository->findByDate($date);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $reservations
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function complete(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!preg_match('#^/api/reservations/(\d+)/complete$#', $path, $matches)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid reservation ID']);
            return;
        }

        $reservationId = (int) $matches[1];

        try {
            $success = $this->reservationRepository->complete($reservationId);
            if ($success) {
                // 1. Send WebSocket Broadcast
                $webSocketServer = getenv('WEBSOCKET_SERVER');
                if ($webSocketServer) {
                    // Change /booking to /release for the URL
                    $releaseUrl = str_replace('/booking', '/release', $webSocketServer);

                    $options = [
                        'http' => [
                            'header' => "Content-Type: application/json\r\n",
                            'method' => 'POST',
                            'content' => json_encode(['reservation_id' => $reservationId]),
                            'timeout' => 1
                        ]
                    ];
                    @file_get_contents($releaseUrl, false, stream_context_create($options));
                }
                // 2. Return Success to Vue
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Reservation completed'
                ]);
            } else {
                // Return 404 if rowCount() was 0
                http_response_code(404);
                echo json_encode([
                    'error' => 'Reservation not found or already completed'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to complete reservation'
            ]);
        }
    }
}
