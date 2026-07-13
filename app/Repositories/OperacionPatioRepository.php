<?php

namespace App\Repositories;

class OperacionPatioRepository
{
    public function obtenerEstadoBase(): array
    {
        return [
            'ready' => true,
            'phase' => 'Fase 2 - Estructura base',
        ];
    }
}
