<?php

declare(strict_types=1);

// Run: php tests/database_connection_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\ConnectionFactory;
use App\Database\ConnectionInterface;
use App\Database\Mysql;
use App\Database\Postgresql;
use App\Database\Sqlite;

function verifyDatabase(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$original = $_ENV;

try {
    foreach (['mysql' => Mysql::class, 'pgsql' => Postgresql::class, 'sqlite' => Sqlite::class] as $driver => $expected) {
        $_ENV['DATABASE_DRIVER'] = $driver;
        $connection = ConnectionFactory::create();
        verifyDatabase($connection instanceof ConnectionInterface && $connection instanceof $expected, "Incorrect connection type: {$driver}");
    }

    $_ENV['DATABASE_DRIVER'] = 'invalid-driver';
    try {
        ConnectionFactory::create();
        throw new RuntimeException('Unknown driver must be rejected');
    } catch (InvalidArgumentException) {
        // Expected.
    }

    unset($_ENV['DATABASE_DRIVER']);
    verifyDatabase(ConnectionFactory::create() instanceof Mysql, 'MySQL must remain the default');

    if (extension_loaded('pdo_sqlite')) {
        $filename = tempnam(sys_get_temp_dir(), 'mvc-basic-sqlite-');
        verifyDatabase($filename !== false, 'Cannot create temporary database file');

        try {
            $_ENV['DATABASE_DRIVER'] = 'sqlite';
            $_ENV['DATABASE_PATH'] = $filename;
            $pdo = ConnectionFactory::create()->connect();
            verifyDatabase($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite', 'SQLite driver not used');
            $pdo->exec('CREATE TABLE example (id INTEGER PRIMARY KEY, label TEXT NOT NULL)');
            $statement = $pdo->prepare('INSERT INTO example (label) VALUES (?)');
            $statement->bindValue(1, 'Hello PDO', PDO::PARAM_STR);
            $statement->execute();
            verifyDatabase($pdo->query('SELECT label FROM example')->fetchColumn() === 'Hello PDO', 'SQLite query failed');
            unset($pdo);
        } finally {
            @unlink($filename);
        }
    } else {
        echo "SQLite integration skipped: pdo_sqlite unavailable\n";
    }
} finally {
    $_ENV = $original;
}

echo "Basic multi-database connections: OK\n";
