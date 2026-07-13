<?php

namespace App\Services;

use App\Repositories\InventarioRepository;

class DashboardService
{
    private InventarioRepository $inventarioRepository;

    public function __construct(?InventarioRepository $inventarioRepository = null)
    {
        $this->inventarioRepository = $inventarioRepository ?? new InventarioRepository();
    }

    public function obtenerResumenTarjetas(): array
    {
        $resumen = $this->inventarioRepository->obtenerResumenDashboard();

        $dbOnline = true;
        try {
            $this->inventarioRepository->contarInventario();
        } catch (\Throwable $e) {
            $dbOnline = false;
        }

        return [
            'cantidad_registros' => (int) ($resumen['cantidad_registros'] ?? 0),
            'total_plataformas' => (int) ($resumen['total_plataformas'] ?? 0),
            'total_ferromex' => (int) ($resumen['total_ferromex'] ?? 0),
            'total_kansas' => (int) ($resumen['total_kansas'] ?? 0),
            'en_encantada' => (int) ($resumen['en_encantada'] ?? 0),
            'otra_ubicacion' => (int) ($resumen['otra_ubicacion'] ?? 0),
            'no_encontrado' => 0,
            'ultima_actualizacion' => $resumen['ultima_actualizacion'] ?? null,
            'estado_servidor' => 'En linea',
            'estado_base_datos' => $dbOnline ? 'Conectada' : 'Sin conexion',
        ];
    }
}