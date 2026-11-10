<?php

declare(strict_types=1);

// Run: php tests/database_configuration_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\Mysql;
use App\Repository\AbstractRepository;
use Dotenv\Dotenv;
use PDO;

if (!class_exists(Mysql::class)) {
    throw new RuntimeException('Mysql class missing');
}
if ((new ReflectionMethod(Mysql::class, 'connectBdd'))->getReturnType()?->__toString() !== PDO::class) {
    throw new RuntimeException('Mysql::connectBdd must return PDO');
}
if (!(new ReflectionClass(AbstractRepository::class))->getConstructor()?->isPublic()) {
    throw new RuntimeException('AbstractRepository must accept a PDO connection');
}

// safeLoad ne doit pas imposer un fichier .env.
$empty = sys_get_temp_dir() . '/mvc-basic-dotenv-' . bin2hex(random_bytes(6));
mkdir($empty);
try {
    Dotenv::createImmutable($empty)->safeLoad();
} finally {
    rmdir($empty);
}

// La configuration incomplète échoue avant toute tentative de connexion.
$keys = ['DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USERNAME', 'DATABASE_PASSWORD'];
$previous = [];
foreach ($keys as $key) {
    $previous[$key] = $_ENV[$key] ?? null;
    unset($_ENV[$key]);
}
try {
    try {
        Mysql::connectBdd();
        throw new RuntimeException('Missing configuration was accepted');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'DATABASE_HOST')) {
            throw $error;
        }
    }
} finally {
    foreach ($previous as $key => $value) {
        if ($value !== null) {
            $_ENV[$key] = $value;
        }
    }
}

echo "Basic PDO configuration: OK\n";
