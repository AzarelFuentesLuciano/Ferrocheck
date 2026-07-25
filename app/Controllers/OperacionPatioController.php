<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/OperacionPatioService.php';

use App\Auth\AuthenticatedUser;
use App\Support\AuthenticatedHeaderBuilder;

class OperacionPatioController
{
    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private string $logoutCsrf,
    ) {
    }

    public function index(): void
    {
        $service = new \App\Services\OperacionPatioService();
        $contexto = $service->obtenerContextoInicial();
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $header = AuthenticatedHeaderBuilder::build(
            $this->authenticatedUser,
            $baseUrl . '/index.php?modulo=auth&accion=logout',
            $this->logoutCsrf,
            [
                'systemName' => 'VASCOR OPS',
                'systemSubtitle' => 'Plataforma Operativa',
                'versionLabel' => 'Versión v1.0',
                'menuLabel' => 'Abrir menú',
                'legacyHooks' => true,
            ],
        );

        require __DIR__ . '/../Views/operaciones-patio/operaciones-patio.php';
    }
}
