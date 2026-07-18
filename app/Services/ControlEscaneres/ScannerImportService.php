<?php

namespace App\Services\ControlEscaneres;

use App\Repositories\ControlEscaneres\ControlEscaneresRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ScannerImportService
{
    private const REQUIRED_HEADERS = [
        'tag',
        'marca',
        'modelo',
        'serie',
    ];

    private const HEADER_MAP = [
        'codigointerno' => 'codigo_interno',
        'codigointerno' => 'codigo_interno',
        'idinterno' => 'codigo_interno',
        'tag' => 'tag',
        'marca' => 'marca',
        'modelo' => 'modelo',
        'serie' => 'numero_serie',
        'numerodeserie' => 'numero_serie',
        'noserie' => 'numero_serie',
        'imei' => 'imei',
        'sim' => 'chip',
        'chip' => 'chip',
        'numero' => 'numero_telefonico',
        'numerotelefonico' => 'numero_telefonico',
        'red' => 'red',
        'plan' => 'plan',
        'pin' => 'pin',
        'puk' => 'puk',
        'actividad' => 'actividad',
        'area' => 'area',
        'ubicacion' => 'ubicacion',
        'estado' => 'estado',
        'indiceconservacion' => 'indice_conservacion',
        'activo' => 'activo',
        'observaciones' => 'observaciones',
    ];

    private ControlEscaneresRepository $repository;

    public function __construct(?ControlEscaneresRepository $repository = null)
    {
        $this->repository = $repository ?? new ControlEscaneresRepository();
    }

    public function previsualizar(string $rutaArchivo): array
    {
        $dataset = $this->leerInventario($rutaArchivo);
        $stats = $this->analizarDataset($dataset['rows'], true);

        return [
            'headers' => $dataset['headers_original'],
            'mapped_headers' => $dataset['mapped_headers'],
            'preview_rows' => array_slice($dataset['rows'], 0, 20),
            'stats' => $stats,
        ];
    }

    public function importar(string $rutaArchivo): array
    {
        $dataset = $this->leerInventario($rutaArchivo);
        $stats = $this->analizarDataset($dataset['rows'], false);

        return [
            'stats' => $stats,
            'total_filas' => count($dataset['rows']),
        ];
    }

    private function analizarDataset(array $rows, bool $dryRun): array
    {
        $stats = [
            'nuevos' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => 0,
            'errores_detalle' => [],
        ];

        if (!$dryRun) {
            $this->repository->iniciarTransaccion();
        }

        try {
            foreach ($rows as $index => $row) {
                $linea = $index + 2;
                $codigoInterno = $this->limpiar($row['codigo_interno'] ?? null);
                $tag = $this->limpiar($row['tag'] ?? null);
                $serie = $this->limpiar($row['numero_serie'] ?? null);

                if ($tag === null && $serie === null && $codigoInterno === null) {
                    $stats['omitidos']++;
                    continue;
                }

                try {
                    $existente = $this->repository->buscarExistenteParaImportacion($codigoInterno, $tag, $serie);

                    if (is_array($existente)) {
                        if (!$dryRun) {
                            $this->repository->actualizarDatosTecnicosImportacion((int) $existente['id'], $row);
                        }
                        $stats['actualizados']++;
                        continue;
                    }

                    if (!$dryRun) {
                        $this->repository->crearScanner($row);
                    }
                    $stats['nuevos']++;
                } catch (\Throwable $e) {
                    $stats['errores']++;
                    $stats['errores_detalle'][] = 'Fila ' . $linea . ': ' . $e->getMessage();
                }
            }

            if (!$dryRun) {
                $this->repository->confirmarTransaccion();
            }
        } catch (\Throwable $e) {
            if (!$dryRun) {
                $this->repository->revertirTransaccion();
            }
            throw $e;
        }

        return $stats;
    }

    private function leerInventario(string $rutaArchivo): array
    {
        $spreadsheet = IOFactory::load($rutaArchivo);
        $sheet = $spreadsheet->getSheetByName('INVENTARIO');

        if ($sheet === null) {
            throw new \RuntimeException('No se encontro la hoja INVENTARIO en el archivo Excel.');
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            throw new \RuntimeException('El archivo no contiene filas suficientes para importar.');
        }

        $headerRow = array_shift($rows);
        $headersOriginal = array_values(array_map(static fn ($v): string => trim((string) $v), $headerRow));
        $mappedHeaders = $this->mapearHeaders($headersOriginal);
        $this->validarHeadersRequeridos($mappedHeaders);

        $mappedRows = [];
        foreach ($rows as $row) {
            $record = [];
            $colIndex = 0;
            foreach ($row as $value) {
                $key = $mappedHeaders[$colIndex] ?? null;
                if ($key !== null) {
                    $record[$key] = is_string($value) ? trim($value) : $value;
                }
                $colIndex++;
            }
            $mappedRows[] = $record;
        }

        return [
            'headers_original' => $headersOriginal,
            'mapped_headers' => $mappedHeaders,
            'rows' => $mappedRows,
        ];
    }

    private function mapearHeaders(array $headers): array
    {
        $mapped = [];

        foreach ($headers as $header) {
            $normalizado = $this->normalizarHeader($header);
            $mapped[] = self::HEADER_MAP[$normalizado] ?? null;
        }

        return $mapped;
    }

    private function validarHeadersRequeridos(array $mappedHeaders): void
    {
        $presentes = array_values(array_filter($mappedHeaders, static fn ($h): bool => is_string($h) && $h !== ''));
        $faltantes = [];

        foreach (self::REQUIRED_HEADERS as $required) {
            $target = self::HEADER_MAP[$required] ?? $required;
            if (!in_array($target, $presentes, true)) {
                $faltantes[] = $required;
            }
        }

        if (!empty($faltantes)) {
            throw new \RuntimeException('Faltan encabezados requeridos: ' . implode(', ', $faltantes));
        }
    }

    private function normalizarHeader(string $header): string
    {
        $header = trim($header);
        $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
        $header = strtolower($header);

        return preg_replace('/[^a-z0-9]+/', '', $header) ?? '';
    }

    private function limpiar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
    }
}
