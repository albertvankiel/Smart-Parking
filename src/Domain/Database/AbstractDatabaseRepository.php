<?php

namespace App\Domain\Database;

readonly abstract class AbstractDatabaseRepository
{
    public function __construct(
        protected \PDO $pdo
    ) {
    }
}