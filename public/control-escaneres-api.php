<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ControlEscaneresController;

header('Content-Type: application/json; charset=utf-8');

$controller = new ControlEscaneresController();
$action = (string) ($_POST['action'] ?? '');

try {
    if ($action === 'registrar_scanner') {
        $resultado = $controller->registrarScanner($_POST);
        echo json_encode([
            'success' => true,
            'message' => 'Escaner registrado correctamente.',
            'data' => $resultado,
        ]);
        exit;
    }

    if ($action === 'import_preview') {
        if (!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            throw new RuntimeException('No se recibio el archivo para previsualizacion.');
        }

        $resultado = $controller->previsualizarImportacion($_FILES['archivo']['tmp_name']);
        echo json_encode([
            'success' => true,
            'message' => 'Previsualizacion generada.',
            'data' => $resultado,
        ]);
        exit;
    }

    if ($action === 'import_execute') {
        if (!isset($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            throw new RuntimeException('No se recibio el archivo para importacion.');
        }

        $resultado = $controller->ejecutarImportacion($_FILES['archivo']['tmp_name']);
        echo json_encode([
            'success' => true,
            'message' => 'Importacion completada.',
            'data' => $resultado,
        ]);
        exit;
    }

    throw new RuntimeException('Accion no soportada.');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
