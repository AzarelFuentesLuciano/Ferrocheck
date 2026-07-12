<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelService
{
    private const UMBRAL_COINCIDENCIA = 25.0;

    private function normalizarEncabezado(string $texto): string
    {
        $normalizado = trim($texto);

        if ($normalizado === '') {
            return '';
        }

        $normalizado = mb_convert_encoding($normalizado, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizado);

        if ($normalizado === false) {
            $normalizado = trim($texto);
        }

        return strtolower((string) $normalizado);
    }

    private function obtenerCatalogoColumnas(): array
    {
        $catalogoPath = __DIR__ . '/../../config/catalogo_columnas.php';

        if (!is_file($catalogoPath)) {
            throw new \RuntimeException('No se encontró el catálogo de columnas del inventario.');
        }

        $catalogoOriginal = require $catalogoPath;

        if (!is_array($catalogoOriginal)) {
            throw new \RuntimeException('El catálogo de columnas del inventario es inválido.');
        }

        $catalogo = [];

        foreach ($catalogoOriginal as $entrada) {
            if (!is_array($entrada)) {
                continue;
            }

            $encabezadoOriginal = trim((string) ($entrada['original'] ?? ''));
            $nombreInterno = trim((string) ($entrada['internal'] ?? ''));

            if ($encabezadoOriginal === '' || $nombreInterno === '') {
                continue;
            }

            $catalogo[] = [
                'original' => $encabezadoOriginal,
                'internal' => $nombreInterno,
            ];
        }

        return $catalogo;
    }

    private function obtenerAliasNormalizadosCatalogo(): array
    {
        $catalogo = $this->obtenerCatalogoColumnas();
        $aliasNormalizados = [];

        foreach ($catalogo as $entrada) {
            $aliasNormalizados[$this->normalizarEncabezado((string) $entrada['original'])][] = (string) $entrada['internal'];
        }

        return $aliasNormalizados;
    }

    private function obtenerColumnasInternasCatalogo(): array
    {
        $catalogo = $this->obtenerCatalogoColumnas();
        $columnas = [];

        foreach ($catalogo as $entrada) {
            $interno = (string) $entrada['internal'];

            if (!in_array($interno, $columnas, true)) {
                $columnas[] = $interno;
            }
        }

        return $columnas;
    }

    private function registrarDiagnosticoDeteccion(array $diagnosticos): void
    {
        $logDir = __DIR__ . '/../../logs';
        $logFile = $logDir . '/excel_diagnostico.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $entrada = [
            'timestamp' => date('Y-m-d H:i:s'),
            'diagnostico' => $diagnosticos,
        ];

        error_log(json_encode($entrada, JSON_UNESCAPED_UNICODE) . PHP_EOL, 3, $logFile);
    }

    private function mapearColumnasInventario(array $headersOriginales): array
    {
        $aliasNormalizados = $this->obtenerAliasNormalizadosCatalogo();

        $mapa = [];
        $encabezadosReconocidos = [];
        $encabezadosDesconocidos = [];
        $encabezadosNormalizados = [];
        $ocurrenciasPorEncabezado = [];

        foreach ($headersOriginales as $indice => $headerOriginal) {
            $headerNormalizado = $this->normalizarEncabezado((string) $headerOriginal);
            $encabezadosNormalizados[] = $headerNormalizado;

            if ($headerNormalizado === '') {
                continue;
            }

            if (!isset($aliasNormalizados[$headerNormalizado])) {
                $encabezadosDesconocidos[] = (string) $headerOriginal;
                continue;
            }

            $internos = $aliasNormalizados[$headerNormalizado];
            $ocurrencia = $ocurrenciasPorEncabezado[$headerNormalizado] ?? 0;
            $nombreInterno = $internos[$ocurrencia] ?? end($internos);
            $ocurrenciasPorEncabezado[$headerNormalizado] = $ocurrencia + 1;

            $mapa[$indice] = $nombreInterno;
            $encabezadosReconocidos[] = $nombreInterno;
        }

        return [
            'mapa_columnas' => $mapa,
            'encabezados_normalizados' => $encabezadosNormalizados,
            'encabezados_reconocidos' => array_values(array_unique($encabezadosReconocidos)),
            'encabezados_desconocidos' => $encabezadosDesconocidos,
        ];
    }

    private function validarEstructuraInventario(array $headersOriginales, array $resultadoMapeo): array
    {
        $columnasCatalogadas = $this->obtenerColumnasInternasCatalogo();
        $columnasReconocidas = $resultadoMapeo['encabezados_reconocidos'];
        $columnasFaltantes = array_values(array_diff($columnasCatalogadas, $columnasReconocidas));
        $columnasDesconocidas = array_values(array_filter($resultadoMapeo['encabezados_desconocidos'], function ($valor) {
            return trim((string) $valor) !== '';
        }));

        $coincidencias = count($columnasReconocidas);
        $porcentajeCoincidencia = count($columnasCatalogadas) > 0
            ? round(($coincidencias / count($columnasCatalogadas)) * 100, 2)
            : 0.0;
        $valido = $porcentajeCoincidencia >= self::UMBRAL_COINCIDENCIA;

        return [
            'valido' => $valido,
            'estado' => $valido ? 'Válido' : 'No válido',
            'columnas_reconocidas' => $coincidencias,
            'columnas_desconocidas' => $columnasDesconocidas,
            'columnas_faltantes' => $columnasFaltantes,
            'columnas_encontradas' => $columnasReconocidas,
            'porcentaje_coincidencia' => $porcentajeCoincidencia,
            'columnas_detectadas' => count($headersOriginales),
        ];
    }

    private function detectarHojaInventario($spreadsheet): ?array
    {
        $sheetNames = $spreadsheet->getSheetNames();
        $diagnosticos = [];
        $mejorCoincidencia = null;

        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray();

            if (empty($rows)) {
                $diagnosticos[] = [
                    'hoja' => $sheetName,
                    'columnas_detectadas' => 0,
                    'encabezados_leidos' => [],
                    'encabezados_normalizados' => [],
                    'encabezados_obligatorios_encontrados' => [],
                    'encabezados_obligatorios_faltantes' => [],
                    'motivo_descartado' => 'La hoja no contiene filas para analizar encabezados.',
                ];
                continue;
            }

            $headersLeidos = array_values(array_shift($rows));
            $resultadoMapeo = $this->mapearColumnasInventario($headersLeidos);
            $validacion = $this->validarEstructuraInventario($headersLeidos, $resultadoMapeo);

            $diagnosticos[] = [
                'hoja' => $sheetName,
                'columnas_detectadas' => count($headersLeidos),
                'encabezados_leidos' => $headersLeidos,
                'encabezados_normalizados' => $resultadoMapeo['encabezados_normalizados'],
                'encabezados_reconocidos' => $validacion['columnas_encontradas'],
                'encabezados_desconocidos' => $validacion['columnas_desconocidas'],
                'columnas_faltantes' => $validacion['columnas_faltantes'],
                'porcentaje_coincidencia' => $validacion['porcentaje_coincidencia'],
                'estado' => $validacion['estado'],
                'motivo_descartado' => $validacion['valido']
                    ? 'Hoja candidata válida por coincidencias en catálogo.'
                    : 'Coincidencias insuficientes con catálogo de inventario.',
            ];

            if (
                $mejorCoincidencia === null
                || $validacion['porcentaje_coincidencia'] > $mejorCoincidencia['validacion']['porcentaje_coincidencia']
                || (
                    $validacion['porcentaje_coincidencia'] === $mejorCoincidencia['validacion']['porcentaje_coincidencia']
                    && $validacion['columnas_reconocidas'] > $mejorCoincidencia['validacion']['columnas_reconocidas']
                )
            ) {
                $mejorCoincidencia = [
                    'sheet' => $sheet,
                    'sheet_name' => $sheetName,
                    'headers_originales' => $headersLeidos,
                    'mapa_columnas' => $resultadoMapeo['mapa_columnas'],
                    'validacion' => $validacion,
                ];
            }
        }

        $this->registrarDiagnosticoDeteccion($diagnosticos);

        if ($mejorCoincidencia === null || !$mejorCoincidencia['validacion']['valido']) {
            return null;
        }

        return $mejorCoincidencia;
    }

    private function leerRegistrosInventario(array $rows, array $mapaColumnas): array
    {
        $result = [];

        foreach ($rows as $row) {
            $registro = [];

            foreach ($mapaColumnas as $indice => $nombreInterno) {
                $registro[$nombreInterno] = $row[$indice] ?? null;
            }

            $tieneContenido = false;
            foreach ($registro as $valor) {
                if (trim((string) $valor) !== '') {
                    $tieneContenido = true;
                    break;
                }
            }

            if ($tieneContenido) {
                $result[] = $registro;
            }
        }

        return $result;
    }

    public function cargarInventario(string $rutaArchivo): array
    {
        if (!is_file($rutaArchivo)) {
            throw new \RuntimeException('No se encontró el archivo Excel para cargar el inventario.');
        }

        $spreadsheet = IOFactory::load($rutaArchivo);
        $deteccion = $this->detectarHojaInventario($spreadsheet);

        if ($deteccion === null) {
            throw new \RuntimeException('No se encontró un inventario válido de Ferromex en el archivo Excel.');
        }

        $rows = $deteccion['sheet']->toArray();

        if (empty($rows)) {
            return [];
        }

        array_shift($rows);

        return $this->leerRegistrosInventario($rows, $deteccion['mapa_columnas']);
    }
}
