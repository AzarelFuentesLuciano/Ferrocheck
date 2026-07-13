<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ExcelService
{
    private const UMBRAL_COINCIDENCIA = 25.0;

    private function obtenerContextoArchivo(string $rutaArchivo): array
    {
        $archivo = $_FILES['archivo'] ?? [];
        $nombre = (string) ($archivo['name'] ?? basename($rutaArchivo));
        $mime = (string) ($archivo['type'] ?? '');

        if ($mime === '') {
            $detectado = @mime_content_type($rutaArchivo);
            $mime = is_string($detectado) ? $detectado : 'desconocido';
        }

        return [
            'archivo' => $nombre,
            'mime' => $mime,
            'ruta_temporal' => $rutaArchivo,
            'extension' => strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION)),
        ];
    }

    private function detectarDelimitadorCsv(string $rutaArchivo): string
    {
        $sample = (string) @file_get_contents($rutaArchivo, false, null, 0, 4096);
        if ($sample === '') {
            return ',';
        }

        $linea = strtok($sample, "\r\n");
        $linea = $linea === false ? $sample : $linea;

        $coma = substr_count($linea, ',');
        $puntoComa = substr_count($linea, ';');
        $tab = substr_count($linea, "\t");

        if ($puntoComa >= $coma && $puntoComa >= $tab) {
            return ';';
        }

        if ($tab > $coma && $tab > $puntoComa) {
            return "\t";
        }

        return ',';
    }

    private function detectarTipoArchivo(string $rutaArchivo, array $contexto): array
    {
        $extension = $contexto['extension'];
        $mime = strtolower((string) ($contexto['mime'] ?? ''));

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return [
                'tipo' => $extension,
                'lector' => strtoupper($extension),
                'extension' => $extension,
            ];
        }

        if ($extension === 'csv') {
            return [
                'tipo' => 'csv',
                'lector' => 'CSV',
                'extension' => $extension,
            ];
        }

        $sample = (string) @file_get_contents($rutaArchivo, false, null, 0, 4096);
        $linea = strtok($sample, "\r\n");
        $linea = $linea === false ? $sample : $linea;
        $pareceCsv = $linea !== '' && (
            substr_count($linea, ',') >= 3
            || substr_count($linea, ';') >= 3
            || substr_count($linea, "\t") >= 3
        );

        if (strpos($mime, 'csv') !== false || strpos($mime, 'text/plain') !== false || $pareceCsv) {
            return [
                'tipo' => 'csv',
                'lector' => 'CSV',
                'extension' => $extension,
            ];
        }

        $readerType = IOFactory::identify($rutaArchivo);
        $tipo = strtolower((string) $readerType);
        if (!in_array($tipo, ['csv', 'xlsx', 'xls'], true)) {
            throw new \RuntimeException('Tipo de archivo no soportado para importación de inventario.');
        }

        return [
            'tipo' => $tipo,
            'lector' => strtoupper($tipo),
            'extension' => $extension,
        ];
    }

    private function detectarCodificacionCsv(string $rutaArchivo): array
    {
        $contenido = (string) @file_get_contents($rutaArchivo, false, null, 0, 32768);
        $bom = '';
        $encodingBom = '';

        if (strncmp($contenido, "\xEF\xBB\xBF", 3) === 0) {
            $bom = 'UTF-8 BOM';
            $encodingBom = 'UTF-8';
            $contenido = substr($contenido, 3);
        } elseif (strncmp($contenido, "\xFF\xFE", 2) === 0) {
            $bom = 'UTF-16LE BOM';
            $encodingBom = 'UTF-16LE';
        } elseif (strncmp($contenido, "\xFE\xFF", 2) === 0) {
            $bom = 'UTF-16BE BOM';
            $encodingBom = 'UTF-16BE';
        }

        if ($encodingBom !== '') {
            return [
                'encoding' => $encodingBom,
                'bom' => $bom,
            ];
        }

        $detectado = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);

        if (!is_string($detectado) || $detectado === '') {
            $detectado = 'Windows-1252';
        }

        return [
            'encoding' => $detectado,
            'bom' => $bom,
        ];
    }

    private function crearReader(string $rutaArchivo, array $tipoDetectado, array $csvInfo)
    {
        if ($tipoDetectado['tipo'] === 'csv') {
            $reader = IOFactory::createReader('Csv');

            if ($reader instanceof Csv) {
                $reader->setDelimiter($this->detectarDelimitadorCsv($rutaArchivo));
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
                $reader->setReadDataOnly(true);
                $reader->setInputEncoding((string) ($csvInfo['encoding'] ?? 'UTF-8'));
                $reader->setFallbackEncoding('Windows-1252');
            }

            return $reader;
        }

        $reader = IOFactory::createReaderForFile($rutaArchivo);
        $reader->setReadDataOnly(true);
        return $reader;
    }

    private function repararTextoMojibake(string $texto): string
    {
        $texto = str_replace("\xC2\xA0", ' ', $texto);

        return strtr($texto, [
            'Ã¡' => 'á',
            'Ã©' => 'é',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ãº' => 'ú',
            'Ã¼' => 'ü',
            'Ã±' => 'ñ',
            'Ã�' => 'Á',
            'Ã‰' => 'É',
            'Ã�' => 'Í',
            'Ã“' => 'Ó',
            'Ãš' => 'Ú',
            'Ãœ' => 'Ü',
            'Ã‘' => 'Ñ',
            'Â' => '',
            'â€™' => "'",
            'â€œ' => '"',
            'â€\x9d' => '"',
            'â€“' => '-',
        ]);
    }

    private function transliterarAsciiSeguro(string $texto): string
    {
        $candidatos = [
            $texto,
            @mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1'),
            @mb_convert_encoding($texto, 'UTF-8', 'Windows-1252'),
        ];

        foreach ($candidatos as $candidato) {
            if (!is_string($candidato) || $candidato === '') {
                continue;
            }

            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $candidato);
            if ($ascii !== false && $ascii !== '') {
                return $ascii;
            }
        }

        return $texto;
    }

    private function normalizarEncabezado(string $texto): string
    {
        $normalizado = trim($this->repararTextoMojibake($texto));

        if ($normalizado === '') {
            return '';
        }

        $normalizado = preg_replace('/\s+/u', ' ', $normalizado) ?? $normalizado;
        $normalizado = $this->transliterarAsciiSeguro($normalizado);
        $normalizado = strtolower((string) $normalizado);

        return preg_replace('/[^a-z0-9]+/', '', $normalizado) ?? '';
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

    private function registrarDiagnosticoDeteccion(array $diagnostico): void
    {
        $logDir = __DIR__ . '/../../logs';
        $logFile = $logDir . '/excel_diagnostico.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $entrada = ['timestamp' => date('Y-m-d H:i:s')] + $diagnostico;

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

    private function detectarHojaInventario($spreadsheet, array $contextoDiagnostico): ?array
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

        $this->registrarDiagnosticoDeteccion([
            'archivo_detectado' => $contextoDiagnostico['archivo'] ?? '',
            'tipo_detectado' => $contextoDiagnostico['tipo_detectado'] ?? '',
            'codificacion_detectada' => $contextoDiagnostico['codificacion_detectada'] ?? '',
            'encabezados_originales' => $mejorCoincidencia['headers_originales'] ?? [],
            'encabezados_normalizados' => $diagnosticos[count($diagnosticos) - 1]['encabezados_normalizados'] ?? [],
            'diagnostico' => $diagnosticos,
        ]);

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

        $contexto = $this->obtenerContextoArchivo($rutaArchivo);
        $tipoDetectado = $this->detectarTipoArchivo($rutaArchivo, $contexto);
        $csvInfo = ['encoding' => '', 'bom' => ''];

        if ($tipoDetectado['tipo'] === 'csv') {
            $csvInfo = $this->detectarCodificacionCsv($rutaArchivo);
        }

        $this->registrarDiagnosticoDeteccion([
            'archivo_detectado' => $contexto['archivo'],
            'tipo_detectado' => $tipoDetectado['tipo'],
            'lector_detectado' => $tipoDetectado['lector'],
            'mime_detectado' => $contexto['mime'],
            'codificacion_detectada' => $csvInfo['encoding'],
            'bom_detectado' => $csvInfo['bom'],
        ]);

        try {
            $reader = $this->crearReader($rutaArchivo, $tipoDetectado, $csvInfo);
            $spreadsheet = $reader->load($rutaArchivo);
        } catch (\Throwable $e) {
            $this->registrarDiagnosticoDeteccion([
                'archivo_detectado' => $contexto['archivo'],
                'tipo_detectado' => $tipoDetectado['tipo'],
                'codificacion_detectada' => $csvInfo['encoding'],
                'motivo_rechazo' => 'Error al abrir archivo: ' . $e->getMessage(),
            ]);

            throw $e;
        }

        $deteccion = $this->detectarHojaInventario($spreadsheet, [
            'archivo' => $contexto['archivo'],
            'tipo_detectado' => $tipoDetectado['tipo'],
            'codificacion_detectada' => $csvInfo['encoding'],
        ]);

        if ($deteccion === null) {
            $this->registrarDiagnosticoDeteccion([
                'archivo_detectado' => $contexto['archivo'],
                'tipo_detectado' => $tipoDetectado['tipo'],
                'codificacion_detectada' => $csvInfo['encoding'],
                'motivo_rechazo' => 'Archivo no válido: encabezados incompatibles con catálogo Ferromex.',
            ]);
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
