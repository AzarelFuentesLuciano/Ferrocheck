<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Endpoint legado sin consumidores ejecutables. Sus escrituras usaban un esquema
// anterior incompatible; se conserva el archivo para responder de forma explícita.
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint deshabilitado. Utiliza el módulo vigente de Control de Escáneres.',
]);
