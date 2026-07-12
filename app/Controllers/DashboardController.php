<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/DashboardService.php';

use App\Services\DashboardService;

class DashboardController
{
    public function index(): void
    {
        require __DIR__ . '/../Views/inventario/importar.php';
    }

    public function resumenTarjetas(): void
    {
        header('Content-Type: application/json');

        try {
            $service = new DashboardService();
            $data = $service->obtenerResumenTarjetas();

            echo json_encode([
                'success' => true,
                'data' => $data,
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