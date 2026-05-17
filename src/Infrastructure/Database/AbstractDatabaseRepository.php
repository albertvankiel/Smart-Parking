<?php

namespace App\Infrastructure\Database;

readonly abstract class AbstractDatabaseRepository
{
    public function __construct(
        protected \PDO $pdo
    ) {
    }
}
