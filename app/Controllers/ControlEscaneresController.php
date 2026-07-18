<?php

namespace App\Controllers;

use App\Services\ControlEscaneres\ControlEscaneresService;
use App\Services\ControlEscaneres\ScannerImportService;

class ControlEscaneresController
{
    private ControlEscaneresService $service;
    private ScannerImportService $importService;

    public function __construct(
        ?ControlEscaneresService $service = null,
        ?ScannerImportService $importService = null
    ) {
        $this->service = $service ?? new ControlEscaneresService();
        $this->importService = $importService ?? new ScannerImportService();
    }

    public function obtenerDatosCatalogo(): array
    {
        return $this->service->obtenerCatalogoMaestro();
    }

    public function registrarScanner(array $payload): array
    {
        return $this->service->registrarScanner($payload);
    }

    public function previsualizarImportacion(string $rutaArchivo): array
    {
        return $this->importService->previsualizar($rutaArchivo);
    }

    public function ejecutarImportacion(string $rutaArchivo): array
    {
        return $this->importService->importar($rutaArchivo);
    }
}
