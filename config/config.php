<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', '/Ferrocheck/public');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'ferrocheck');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', '');
}

if (!defined('DB_DSN')) {
    define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4');
}

return [
    "app_name" => "FerroCheck",
    "version" => "1.0.0",
    "timezone" => "America/Mexico_City",
    "database" => [
        "driver" => "mysql",
        "host" => DB_HOST,
        "port" => DB_PORT,
        "database" => DB_NAME,
        "username" => DB_USER,
        "password" => DB_PASSWORD,
        "charset" => "utf8mb4"
    ]
];