<?php

namespace App\UI\Traits;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

trait RequireAuth
{
    /**
     * Validates JWT token from authorization header.
     *
     * @return int User ID if authenticated.
     * @throws Exception If token is invalid or missing.
     */
    public function authenticate(): int
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new Exception('Unauthorized. Invalid or missing token.', 401);
        }

        $jwt = $matches[1];
        $secretKey = getenv('JWT_SECRET');

        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
            return (int) $decoded->sub;
        } catch (Exception $e) {
            throw new Exception('Unauthorized. Invalid token.', 401);
        }
    }
}
