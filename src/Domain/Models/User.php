<?php

namespace App\Domain\Models;

readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $password
    ) {
    }
}
