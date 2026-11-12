<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/** Connexion PDO PostgreSQL. */
final class Postgresql implements ConnectionInterface
{
    /**
     * @return PDO Connexion PostgreSQL configurée.
     * @throws RuntimeException Si la configuration est incomplète ou l'extension absente.
     */
    public function connect(): PDO
    {
        if (!extension_loaded('pdo_pgsql')) {
            throw new RuntimeException('Extension PHP pdo_pgsql requise.');
        }

        $database = $_ENV['DATABASE_NAME'] ?? '';
        if ($database === '') {
            throw new RuntimeException('DATABASE_NAME est requis pour PostgreSQL.');
        }

        $host = $_ENV['DATABASE_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DATABASE_PORT'] ?? '5432';

        return new PDO(
            "pgsql:host={$host};port={$port};dbname={$database}",
            $_ENV['DATABASE_USERNAME'] ?? '',
            $_ENV['DATABASE_PASSWORD'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}
