<?php

namespace App\Infrastructure\Database\User;

use App\Domain\Models\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Database\AbstractDatabaseRepository;

readonly class MySQLUserRepository extends AbstractDatabaseRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        $stmnt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmnt->execute([$email]);

        $row = $stmnt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new User($row['id'], $row['email'], $row['password']);
    }
}