<?php

namespace App\Services;

use App\Repositories\OperacionPatioRepository;

class OperacionPatioService
{
    private OperacionPatioRepository $operacionPatioRepository;

    public function __construct(?OperacionPatioRepository $operacionPatioRepository = null)
    {
        $this->operacionPatioRepository = $operacionPatioRepository ?? new OperacionPatioRepository();
    }

    public function obtenerContextoInicial(): array
    {
        return [
            'modulo' => 'Operaciones de Patio',
            'estado' => $this->operacionPatioRepository->obtenerEstadoBase(),
        ];
    }
}
