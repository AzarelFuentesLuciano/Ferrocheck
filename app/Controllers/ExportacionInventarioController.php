<?php

namespace App\Controllers;

use App\Services\InventarioExportService;

class ExportacionInventarioController
{
    public function exportar(): void
    {
        try {
            $equiposRaw = (string) ($_POST['equipos_filtrados'] ?? '');
            $equipos = [];

            if (trim($equiposRaw) !== '') {
                $decoded = json_decode($equiposRaw, true);
                if (is_array($decoded)) {
                    $equipos = array_map(static fn ($item): string => (string) $item, $decoded);
                }
            }

            $service = new InventarioExportService();
            $service->exportarInventario($equipos);
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
