<?php

namespace App\Database;

use PDO;
use RuntimeException;

final class Mysql implements ConnectionInterface
{
    /**
     * Crée une connexion PDO MySQL à partir des variables d'environnement DATABASE_*. Prend en charge un port explicite ou indiqué dans l'hôte ; active les erreurs par exception et les requêtes préparées natives.
     * @return \PDO Connexion MySQL configurée.
     * @throws \PDOException Si la connexion échoue.
     */
    public function connect(): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('Extension PHP pdo_mysql requise.');
        }

        if (empty($_ENV['DATABASE_NAME'])) {
            throw new RuntimeException('DATABASE_NAME est requis pour MySQL.');
        }
        $hostValue = $_ENV["DATABASE_HOST"] ?? "localhost";
        $port = $_ENV["DATABASE_PORT"] ?? null;

        if ($port === null && str_contains($hostValue, ':')) {
            [$hostValue, $port] = explode(':', $hostValue, 2);
        }

        $dsn = 'mysql:host=' . $hostValue
            . (!empty($port) ? ';port=' . $port : '')
            . ';dbname=' . $_ENV["DATABASE_NAME"]
            . ';charset=utf8mb4';

        // Connexion PDO partagée par le conteneur.
        return new \PDO(
            $dsn,
            $_ENV["DATABASE_USERNAME"],
            $_ENV["DATABASE_PASSWORD"],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /** Compatibilité avec l'ancien point d'entrée statique. */
    public static function connectBdd(): PDO
    {
        return (new self())->connect();
    }
}
