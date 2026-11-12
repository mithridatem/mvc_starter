<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/**
 * Crée une connexion PDO MySQL à la demande, sans conteneur de dépendances.
 *
 * Les variables DATABASE_* sont chargées depuis .env par le point d'entrée.
 */
final class Mysql implements ConnectionInterface
{
    /** Connexion conforme au contrat commun, sans dépendance à un conteneur. */
    public function connect(): PDO
    {
        return self::connectBdd();
    }

    /**
     * @return PDO Connexion MySQL prête à être injectée dans un repository.
     */
    public static function connectBdd(): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('Extension PHP pdo_mysql requise.');
        }
        $required = [
            'DATABASE_HOST',
            'DATABASE_PORT',
            'DATABASE_NAME',
            'DATABASE_USERNAME',
            'DATABASE_PASSWORD',
        ];

        foreach ($required as $name) {
            if (!isset($_ENV[$name])) {
                throw new RuntimeException("Variable d'environnement manquante : {$name}");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_PORT'],
            $_ENV['DATABASE_NAME']
        );

        return new PDO(
            $dsn,
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}
