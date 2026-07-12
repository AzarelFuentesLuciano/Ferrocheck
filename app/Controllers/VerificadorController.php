<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/VerificadorService.php';

use App\Services\VerificadorService;

class VerificadorController
{
    public function verificar(): void
    {
        header('Content-Type: application/json');

        $equiposRaw = (string) ($_POST['equipos'] ?? '');

        if (trim($equiposRaw) === '') {
            echo json_encode([
                'success' => false,
                'message' => 'No se recibieron códigos para verificar.',
            ]);
            return;
        }

        try {
            $equipos = preg_split('/\r\n|\r|\n/', $equiposRaw) ?: [];
            $service = new VerificadorService();
            $resultado = $service->verificarEquipos($equipos);

            echo json_encode([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
