<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\InventarioController;

header('Content-Type: application/json');

try {
    $controller = new InventarioController();
    $controller->importar();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la importacion: ' . $e->getMessage(),
    ]);
}
