<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

class PessimisticLockTest extends TestCase
{
    private string $apiUrl = 'http://web:80/api/reservations';
    private string $token1;
    private string $token2;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate valid JWTs for two different users
        $secretKey = getenv('JWT_SECRET');
        if (!$secretKey) {
            $this->markTestSkipped('JWT_SECRET is not set in the environment.');
        }

        $payload1 = ['sub' => 1, 'iat' => time(), 'exp' => time() + 3600];
        $payload2 = ['sub' => 2, 'iat' => time(), 'exp' => time() + 3600];

        $this->token1 = JWT::encode($payload1, $secretKey, 'HS256');
        $this->token2 = JWT::encode($payload2, $secretKey, 'HS256');
    }

    public function testConcurrentBookingsPreventDoubleBooking(): void
    {
        $randomDay = rand(1, 28);
        $randomMonth = rand(1, 12);
        $randomYear = rand(2030, 2050);
        $dateString = sprintf("%04d-%02d-%02d", $randomYear, $randomMonth, $randomDay);
        $payload = json_encode([
            'spot_id' => 3,
            'start_time' => "{$dateString} 08:00:00",
            'end_time' => "{$dateString} 12:00:00"
        ]);


        $ch1 = curl_init($this->apiUrl);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token1
        ]);

        $ch2 = curl_init($this->apiUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token2
        ]);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch1);
        curl_multi_add_handle($mh, $ch2);

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        $status1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        $status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch1);
        curl_multi_remove_handle($mh, $ch2);
        curl_multi_close($mh);

        $statuses = [$status1, $status2];
        sort($statuses);

        $this->assertEquals(201, $statuses[0], "One request should have succeeded.");
        $this->assertEquals(409, $statuses[1], "The other request should have failed due to the pessimistic lock.");
    }
}