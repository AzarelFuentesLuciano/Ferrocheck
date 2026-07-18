<?php

namespace App\Services\ControlEscaneres;

use App\Repositories\ControlEscaneres\ControlEscaneresRepository;

class ControlEscaneresService
{
    private ControlEscaneresRepository $repository;

    public function __construct(?ControlEscaneresRepository $repository = null)
    {
        $this->repository = $repository ?? new ControlEscaneresRepository();
    }

    public function obtenerCatalogoMaestro(): array
    {
        $tablaLista = $this->repository->existeTablaScanners();

        return [
            'tabla_lista' => $tablaLista,
            'resumen' => $this->repository->obtenerResumenCatalogo(),
            'scanners' => $this->repository->obtenerCatalogo(),
        ];
    }

    public function registrarScanner(array $payload): array
    {
        $marca = trim((string) ($payload['marca'] ?? ''));
        $modelo = trim((string) ($payload['modelo'] ?? ''));

        if ($marca === '' || $modelo === '') {
            throw new \InvalidArgumentException('Marca y modelo son obligatorios para registrar un escaner.');
        }

        return $this->repository->crearScanner($payload);
    }
}
