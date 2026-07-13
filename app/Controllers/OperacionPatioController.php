<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/OperacionPatioService.php';

class OperacionPatioController
{
    public function index(): void
    {
        $service = new \App\Services\OperacionPatioService();
        $contexto = $service->obtenerContextoInicial();

        require __DIR__ . '/../Views/operaciones-patio/operaciones-patio.php';
    }
}
