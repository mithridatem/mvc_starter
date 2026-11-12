<?php

declare(strict_types=1);

// Run: php tests/database_connection_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\ConnectionFactory;
use App\Database\ConnectionInterface;
use App\Database\Mysql;
use App\Database\Postgresql;
use App\Database\Sqlite;
use App\Entity\User;
use App\Repository\UserRepository;

function checkDatabase(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$old = $_ENV;
try {
    foreach (['mysql' => Mysql::class, 'pgsql' => Postgresql::class, 'sqlite' => Sqlite::class] as $driver => $class) {
        $_ENV['DATABASE_DRIVER'] = $driver;
        $adapter = ConnectionFactory::create();
        checkDatabase($adapter instanceof ConnectionInterface && $adapter instanceof $class, "Wrong adapter: {$driver}");
    }

    $_ENV['DATABASE_DRIVER'] = 'unknown';
    try {
        ConnectionFactory::create();
        throw new RuntimeException('Unknown database driver accepted.');
    } catch (InvalidArgumentException) {
        // Expected.
    }

    unset($_ENV['DATABASE_DRIVER']);
    checkDatabase(ConnectionFactory::create() instanceof Mysql, 'Default driver must remain mysql.');

    if (extension_loaded('pdo_sqlite')) {
        $file = tempnam(sys_get_temp_dir(), 'mvc-sqlite-');
        if ($file === false) {
            throw new RuntimeException('Cannot create temporary SQLite database.');
        }

        try {
            $_ENV['DATABASE_DRIVER'] = 'sqlite';
            $_ENV['DATABASE_PATH'] = $file;

            $pdo = ConnectionFactory::create()->connect();
            checkDatabase($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite', 'SQLite connection failed.');
            $schema = file_get_contents(dirname(__DIR__) . '/database/sqlite.sql');
            checkDatabase($schema !== false, 'Missing SQLite schema.');
            $pdo->exec($schema);

            $repository = new UserRepository($pdo);
            $user = (new User('Ada', 'Lovelace', 'ada@example.test', 'hashed-password'))
                ->setGrant('ROLE_USER');

            $created = $repository->create($user);
            checkDatabase($created instanceof User && $created->getId() !== null, 'SQLite insertion failed.');
            checkDatabase($repository->find($created->getId())?->getEmail() === 'ada@example.test', 'SQLite find failed.');
            checkDatabase($repository->findByEmail('ada@example.test')?->getGrant() === 'ROLE_USER', 'SQLite email lookup failed.');
            checkDatabase(count($repository->findAll()) === 1, 'SQLite findAll failed.');
        } finally {
            unset($pdo, $repository);
            @unlink($file);
        }
    } else {
        echo "SQLite integration skipped: pdo_sqlite not installed.\n";
    }
} finally {
    $_ENV = $old;
}

echo "Database adapter selection and SQLite integration: OK\n";
