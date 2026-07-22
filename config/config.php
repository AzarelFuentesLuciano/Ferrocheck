<?php

if (!defined('BASE_URL')) {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0] ?? $host;
    $isLocal = in_array($host, ['localhost', '127.0.0.1'], true);

    define('BASE_URL', $isLocal ? '/Ferrocheck/public' : '');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'vascor-db');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'vascor');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'vascor');
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'vascor123');
}

if (!defined('DB_DSN')) {
    define(
        'DB_DSN',
        'mysql:host=' . DB_HOST .
        ';port=' . DB_PORT .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4'
    );
}

return [
    "app_name" => "FerroCheck",
    "version" => "1.0.0",
    "timezone" => "America/Mexico_City",
    "database" => [
        "driver"   => "mysql",
        "host"     => DB_HOST,
        "port"     => DB_PORT,
        "database" => DB_NAME,
        "username" => DB_USER,
        "password" => DB_PASSWORD,
        "charset"  => "utf8mb4"
    ]
];
