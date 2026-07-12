<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/DetallePlataformaService.php';

class DetallePlataformaController
{
    public function detalle(): void
    {
        header('Content-Type: application/json');

        $codigo = (string) ($_POST['codigo_equipo'] ?? '');

        if (trim($codigo) === '') {
            echo json_encode([
                'success' => false,
                'message' => 'No se recibió el código de equipo.',
            ]);
            return;
        }

        try {
            $service = new \App\Services\DetallePlataformaService();
            $detalle = $service->obtenerDetalle($codigo);

            if ($detalle === null) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El equipo solicitado ya no existe en el inventario.',
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $detalle,
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
