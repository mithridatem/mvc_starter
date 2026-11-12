<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/** Connexion PDO SQLite (fichier local hors du dossier public). */
final class Sqlite implements ConnectionInterface
{
    /**
     * @return PDO Connexion SQLite.
     * @throws RuntimeException Si le chemin est invalide ou l'extension absente.
     */
    public function connect(): PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('Extension PHP pdo_sqlite requise.');
        }

        $path = trim($_ENV['DATABASE_PATH'] ?? 'var/database.sqlite');
        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('DATABASE_PATH doit désigner un fichier SQLite.');
        }

        // Chemin relatif à la racine du projet, pas au répertoire de lancement PHP.
        $absolute = str_starts_with($path, '/') || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path);
        $filename = $absolute ? $path : dirname(__DIR__, 2) . '/' . $path;
        $directory = realpath(dirname($filename));
        if ($directory === false || !is_dir($directory)) {
            throw new RuntimeException('Dossier SQLite absent : créer le dossier parent de DATABASE_PATH.');
        }

        $public = realpath(dirname(__DIR__, 2) . '/public');
        if ($public !== false && ($directory === $public || str_starts_with($directory, $public . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Ne pas stocker SQLite dans le répertoire public.');
        }

        return new PDO('sqlite:' . $filename, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
