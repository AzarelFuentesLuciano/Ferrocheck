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

        return [
            'inventario_ferromex' => (int) ($resumen['inventario_ferromex'] ?? 0),
            'en_encantada' => (int) ($resumen['en_encantada'] ?? 0),
            'otra_ubicacion' => (int) ($resumen['otra_ubicacion'] ?? 0),
            'no_encontrado' => 0,
            'ultima_actualizacion' => $resumen['ultima_actualizacion'] ?? null,
        ];
    }
}