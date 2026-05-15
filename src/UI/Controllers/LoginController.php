<?php

namespace App\UI\Controllers;

use Firebase\JWT\JWT;
use App\Domain\Repositories\UserRepositoryInterface;

readonly class LoginController
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function login(): void
    {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $this->userRepository->findByEmail($email);

        // Use a dummy hash if the user doesn't exist to prevent timing attacks
        $hashToVerify = $user ? $user->password : '$2y$10$abcdefghijklmnopqrstuv';

        $passwordMatches = password_verify($password, $user->password);

        if ($user && $passwordMatches) {
            $payload = [
                'iss' => getenv('APP_URL'),
                'sub' => $user->id,
                'iat' => time(),
                'exp' => time() + 3600
            ];

            $secretKey = getenv('JWT_SECRET');
            if (!$secretKey || strlen($secretKey) < 32) {
                http_response_code(500);
                echo json_encode(['error' => 'Server misconfiguration']);
                return;
            }

            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['token' => $jwt]);
            return;
        }

        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid credentials']);
    }
}