<?php

namespace App\UI\Controllers\Reservation;

use App\Domain\Repositories\ReservationRepositoryInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

readonly class ReservationController
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository
    ) {
    }

    public function store(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Missing or invalid token']);
            return;
        }

        $jwt = $matches[1];
        $secretKey = getEnv('JWT_SECRET');

        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
            $userId = (int) $decoded->sub;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Unauthorized. Invalid token.']);
            return;
        }

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
            $reservation = $this->reservationRepository->bookSpot($userId, $spotId, $startTime, $endTime);

            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $reservation
            ]);
        } catch (Exception $e) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage() 
            ]);
        }

    }
}
