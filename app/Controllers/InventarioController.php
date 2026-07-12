<?php

namespace App\Controllers;

use App\Services\InventarioService;

class InventarioController
{
    public function importar(): void
    {
        $rutaArchivo = $_FILES['archivo']['tmp_name'] ?? '';
        $nombreArchivo = $_FILES['archivo']['name'] ?? '';
        $accion = $_POST['accion'] ?? 'analizar';

        if (empty($rutaArchivo) || empty($nombreArchivo)) {
            header('Content-Type: application/json');
            echo json_encode([
                'archivo' => $nombreArchivo,
                'registros' => 0,
                'estado' => 'No válido',
                'valido' => false,
                'mensaje' => 'No se recibió un archivo válido.',
            ]);
            return;
        }

        $inventarioService = new InventarioService();

        if ($accion === 'importar') {
            try {
                $registrosImportados = $inventarioService->importarDesdeArchivo($rutaArchivo);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'registros_importados' => $registrosImportados,
                ]);
                return;
            } catch (\Throwable $e) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
                return;
            }
        }

        $resultado = $inventarioService->analizarArchivo($rutaArchivo, $nombreArchivo);

        header('Content-Type: application/json');
        echo json_encode($resultado);
    }
}

