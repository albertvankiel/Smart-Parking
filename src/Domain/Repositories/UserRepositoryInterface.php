<?php

namespace App\Domain\Repositories;

use App\Domain\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
}