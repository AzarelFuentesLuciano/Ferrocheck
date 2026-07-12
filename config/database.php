<?php

declare(strict_types=1);

use PDO;

require_once __DIR__ . '/config.php';

/**
 * Creates and returns a PDO connection for MariaDB.
 */
function crearConexionPDO(): PDO
{
    return new PDO(
        DB_DSN,
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
