<?php

namespace App\Infrastructure\Database\User;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Models\User;

readonly class RedisUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private \Redis $redis,
        private UserRepositoryInterface $baseUserRepository
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $cacheKey = "user:email:{$email}";

        $cachedUser = $this->redis->get($cacheKey);

        if ($cachedUser) {
            $data = json_decode($cachedUser, true);
            return new User($data['id'], $data['email'], $data['password']);
        }

        $user = $this->baseUserRepository->findByEmail($email);

        if ($user) {
            $this->redis->set($cacheKey, json_encode([
                'id'       => $user->id,
                'email'    => $user->email,
                'password' => $user->password
            ]), 3600);
        }

        return $user;
    }
}
