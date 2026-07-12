<?php

namespace App\Services;

use App\Repositories\InventarioRepository;

class InventarioService
{
    private ExcelService $excelService;
    private ?InventarioRepository $inventarioRepository;

    public function __construct(?ExcelService $excelService = null, ?InventarioRepository $inventarioRepository = null)
    {
        $this->excelService = $excelService ?? new ExcelService();
        $this->inventarioRepository = $inventarioRepository;
    }

    private function obtenerInventarioRepository(): InventarioRepository
    {
        if ($this->inventarioRepository === null) {
            $this->inventarioRepository = new InventarioRepository();
        }

        return $this->inventarioRepository;
    }

    public function analizarArchivo(string $rutaArchivo, string $nombreArchivo): array
    {
        if (empty($rutaArchivo)) {
            return [
                'archivo' => $nombreArchivo,
                'registros' => 0,
                'estado' => 'No válido',
                'valido' => false,
                'mensaje' => 'No se recibió una ruta de archivo válida.',
            ];
        }

        try {
            $registros = $this->excelService->cargarInventario($rutaArchivo);

            return [
                'archivo' => $nombreArchivo,
                'registros' => count($registros),
                'estado' => 'Válido',
                'valido' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'archivo' => $nombreArchivo,
                'registros' => 0,
                'estado' => 'No válido',
                'valido' => false,
                'mensaje' => $e->getMessage(),
                'mensaje_excepcion' => $e->getMessage(),
            ];
        }
    }

    public function importarDesdeArchivo(string $rutaArchivo): int
    {
        if (empty($rutaArchivo)) {
            throw new \RuntimeException('No se recibió una ruta de archivo válida para importar.');
        }

        $registros = $this->excelService->cargarInventario($rutaArchivo);

        return $this->importarInventario($registros);
    }

    public function importarInventario(array $registros): int
    {
        if (empty($registros)) {
            throw new \RuntimeException('No se encontraron registros para importar.');
        }

        $repositorio = $this->obtenerInventarioRepository();
        return $repositorio->insertarRegistros($registros);
    }
}
