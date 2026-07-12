<?php

namespace App\Services;

use App\Repositories\InventarioRepository;

class DetallePlataformaService
{
    private InventarioRepository $inventarioRepository;

    public function __construct(?InventarioRepository $inventarioRepository = null)
    {
        $this->inventarioRepository = $inventarioRepository ?? new InventarioRepository();
    }

    public function obtenerDetalle(string $codigo): ?array
    {
        $codigoNormalizado = strtoupper(trim($codigo));

        if ($codigoNormalizado === '') {
            return null;
        }

        return $this->inventarioRepository->buscarEquipoPorCodigo($codigoNormalizado);
    }
}
