<?php

declare(strict_types=1);

namespace App\Database;

use InvalidArgumentException;

/** Sélectionne un adaptateur PDO à partir de DATABASE_DRIVER. */
final class ConnectionFactory
{
    /**
     * @return ConnectionInterface Adaptateur configuré pour le moteur choisi.
     * @throws InvalidArgumentException Si le driver est inconnu.
     */
    public static function create(): ConnectionInterface
    {
        return match (strtolower(trim($_ENV['DATABASE_DRIVER'] ?? 'mysql'))) {
            'mysql' => new Mysql(),
            'pgsql' => new Postgresql(),
            'sqlite' => new Sqlite(),
            default => throw new InvalidArgumentException(
                'DATABASE_DRIVER non supporté (mysql, pgsql, sqlite).'
            ),
        };
    }
}
