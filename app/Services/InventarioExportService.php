<?php

namespace App\Services;

use App\Repositories\InventarioRepository;

class InventarioExportService
{
    private InventarioRepository $inventarioRepository;
    private XlsxExportService $xlsxExportService;

    public function __construct(?InventarioRepository $inventarioRepository = null, ?XlsxExportService $xlsxExportService = null)
    {
        $this->inventarioRepository = $inventarioRepository ?? new InventarioRepository();
        $this->xlsxExportService = $xlsxExportService ?? new XlsxExportService();
    }

    /**
     * @param array<int, string> $equipos
     */
    public function exportarInventario(array $equipos = []): void
    {
        if (!$this->inventarioRepository->existeTablaInventario()) {
            throw new \RuntimeException('La tabla inventario no existe en la base de datos.');
        }

        $columnas = $this->inventarioRepository->obtenerColumnasInventario();
        $columnas[] = 'fecha_importacion';

        $headerLabels = $this->obtenerEtiquetasEncabezado();
        $registros = $this->inventarioRepository->obtenerRegistrosParaExportacion($equipos);

        $filename = 'FerroCheck_' . date('Y-m-d_H-i') . '.xlsx';
        $dateColumns = [
            'fecha_de_ultimo_movimiento',
            'eta',
            'eti',
            'fecha_de_situado',
            'fecha_importacion',
        ];

        $this->xlsxExportService->stream($filename, $registros, $columnas, $headerLabels, $dateColumns);
    }

    /**
     * @return array<string, string>
     */
    private function obtenerEtiquetasEncabezado(): array
    {
        $catalogoPath = __DIR__ . '/../../config/catalogo_columnas.php';
        $catalogo = is_file($catalogoPath) ? require $catalogoPath : [];
        $labels = [];

        if (is_array($catalogo)) {
            foreach ($catalogo as $entrada) {
                if (!is_array($entrada)) {
                    continue;
                }

                $internal = trim((string) ($entrada['internal'] ?? ''));
                $original = trim((string) ($entrada['original'] ?? ''));

                if ($internal !== '' && $original !== '' && !array_key_exists($internal, $labels)) {
                    $labels[$internal] = $original;
                }
            }
        }

        $labels['fecha_importacion'] = 'Fecha de Importación';

        return $labels;
    }
}
