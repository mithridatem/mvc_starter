<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/** Contrat commun pour ouvrir une connexion PDO. */
interface ConnectionInterface
{
    public function connect(): PDO;
}
