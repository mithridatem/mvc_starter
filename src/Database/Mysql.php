<?php

namespace App\Database;

final class Mysql
{
    /**
     * Crée une connexion PDO MySQL à partir des variables d'environnement DATABASE_*. Prend en charge un port explicite ou indiqué dans l'hôte ; active les erreurs par exception et les requêtes préparées natives.
     * @return \PDO Connexion MySQL configurée.
     * @throws \PDOException Si la connexion échoue.
     */
    public static function connectBdd(): \PDO
    {
        $hostValue = $_ENV["DATABASE_HOST"] ?? "localhost";
        $port = $_ENV["DATABASE_PORT"] ?? null;

        if ($port === null && str_contains($hostValue, ':')) {
            [$hostValue, $port] = explode(':', $hostValue, 2);
        }

        $dsn = 'mysql:host=' . $hostValue
            . (!empty($port) ? ';port=' . $port : '')
            . ';dbname=' . $_ENV["DATABASE_NAME"]
            . ';charset=utf8mb4';

        // Creation d'un objet PDO
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
}