<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelService
{
    private const UMBRAL_COINCIDENCIA = 60.0;
    private const MAX_FILAS_CANDIDATAS_ENCABEZADO = 20;

    private function obtenerContextoArchivo(string $rutaArchivo): array
    {
        $archivo = $_FILES['archivo'] ?? [];

        return [
            'archivo' => (string) ($archivo['name'] ?? basename($rutaArchivo)),
            'mime_detectado' => (string) ($archivo['type'] ?? (mime_content_type($rutaArchivo) ?: 'desconocido')),
            'ruta_temporal' => $rutaArchivo,
        ];
    }

    private function repararTextoMojibake(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        $textoLimpio = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $texto);
        $textoLimpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $textoLimpio) ?? $textoLimpio;

        $candidatos = [
            $textoLimpio,
            @mb_convert_encoding($textoLimpio, 'UTF-8', 'Windows-1252'),
            @mb_convert_encoding($textoLimpio, 'UTF-8', 'ISO-8859-1'),
            @mb_convert_encoding($textoLimpio, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1'),
        ];

        $seleccionado = $textoLimpio;

        foreach ($candidatos as $candidato) {
            if (!is_string($candidato) || $candidato === '') {
                continue;
            }

            $candidato = strtr($candidato, [
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
                'â€”' => '-',
            ]);

            if (preg_match('/Ã|Â|â€|â€™|�/', $candidato) === 1) {
                continue;
            }

            $seleccionado = $candidato;
            break;
        }

        return trim($seleccionado);
    }

    private function transliterarAsciiSeguro(string $texto): string
    {
        if (class_exists('Transliterator')) {
            $translit = \Transliterator::create('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove; NFC');
            if ($translit !== null) {
                $valor = $translit->transliterate($texto);
                if (is_string($valor) && $valor !== '') {
                    return $valor;
                }
            }
        }

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
        $normalizado = str_replace(['/', '-', '_', '.'], ' ', $normalizado);
        $normalizado = preg_replace('/\s+/u', ' ', $normalizado) ?? $normalizado;
        $normalizado = $this->transliterarAsciiSeguro($normalizado);
        $normalizado = strtolower((string) $normalizado);

        return preg_replace('/[^a-z0-9]+/', '', $normalizado) ?? '';
    }

    private function obtenerAliasVariantes(): array
    {
        return [
            'tipoembarque' => ['tipo_de_embarque'],
            'tipodeembarque' => ['tipo_de_embarque'],
            'entregar' => ['entregar_a'],
            'entregara' => ['entregar_a'],
            'numeroguia' => ['numero_de_guia'],
            'numerodeguia' => ['numero_de_guia'],
            'guia' => ['numero_de_guia'],
            'numerobol' => ['numero_de_bol'],
            'numerodebol' => ['numero_de_bol'],
            'bol' => ['numero_de_bol'],
            'tipoespecificodeequipo' => ['tipo_especifico_de_equipo', 'tipo_especifico_de_equipo_2'],
            'tipogenericodeequipo' => ['tipo_generico_de_equipo'],
            'le' => ['l_e'],
            'estatusviaje' => ['estatus_del_viaje'],
            'estatusdelviaje' => ['estatus_del_viaje'],
            'diaremitido' => ['dia_remitido'],
            'destinoenlinea' => ['destino_en_linea'],
            'limitedecarga' => ['limite_de_carga'],
            'idtren' => ['id_de_tren'],
            'iddetren' => ['id_de_tren'],
        ];
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

        foreach ($this->obtenerAliasVariantes() as $alias => $internos) {
            if (!isset($aliasNormalizados[$alias])) {
                $aliasNormalizados[$alias] = [];
            }

            foreach ($internos as $interno) {
                if (!in_array($interno, $aliasNormalizados[$alias], true)) {
                    $aliasNormalizados[$alias][] = $interno;
                }
            }
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

    private function obtenerEncabezadosEsperadosNormalizados(): array
    {
        $catalogo = $this->obtenerCatalogoColumnas();
        $esperados = [];

        foreach ($catalogo as $entrada) {
            $esperados[] = $this->normalizarEncabezado((string) ($entrada['original'] ?? ''));
        }

        return array_values(array_unique(array_filter($esperados, static fn ($valor): bool => $valor !== '')));
    }

    private function obtenerTotalColumnas(array $rows): int
    {
        $max = 0;

        foreach ($rows as $row) {
            if (is_array($row)) {
                $max = max($max, count($row));
            }
        }

        return $max;
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
        $valido = $porcentajeCoincidencia >= self::UMBRAL_COINCIDENCIA && $coincidencias >= 20;

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

    private function evaluarFilaEncabezado(array $headersLeidos): array
    {
        $resultadoMapeo = $this->mapearColumnasInventario($headersLeidos);
        $validacion = $this->validarEstructuraInventario($headersLeidos, $resultadoMapeo);

        return [
            'headers_leidos' => $headersLeidos,
            'resultado_mapeo' => $resultadoMapeo,
            'validacion' => $validacion,
            'puntaje' => ($validacion['porcentaje_coincidencia'] * 10) + $validacion['columnas_reconocidas'],
        ];
    }

    private function detectarHojaInventario($spreadsheet, array $contextoArchivo): ?array
    {
        $sheetNames = $spreadsheet->getSheetNames();
        $diagnosticos = [];
        $mejorCoincidencia = null;
        $encabezadosEsperados = $this->obtenerCatalogoColumnas();
        $encabezadosEsperadosNormalizados = $this->obtenerEncabezadosEsperadosNormalizados();

        foreach ($sheetNames as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray();
            $cantidadFilas = count($rows);

            if (empty($rows)) {
                $diagnosticos[] = [
                    'hoja' => $sheetName,
                    'filas_detectadas' => 0,
                    'columnas_detectadas' => 0,
                    'encabezados_leidos' => [],
                    'encabezados_normalizados' => [],
                    'encabezados_esperados' => array_map(static fn (array $entrada): string => (string) ($entrada['original'] ?? ''), $encabezadosEsperados),
                    'encabezados_esperados_normalizados' => $encabezadosEsperadosNormalizados,
                    'diferencias' => [
                        'faltantes' => $encabezadosEsperadosNormalizados,
                        'no_esperados' => [],
                    ],
                    'linea_invalido' => __FILE__ . ':' . (__LINE__ - 13),
                    'motivo_descartado' => 'La hoja no contiene filas para analizar encabezados.',
                ];
                continue;
            }

            $limiteFilas = min(self::MAX_FILAS_CANDIDATAS_ENCABEZADO, $cantidadFilas);
            $mejorFila = null;
            $filasEvaluadas = [];

            for ($indiceFila = 0; $indiceFila < $limiteFilas; $indiceFila++) {
                $candidata = array_values((array) ($rows[$indiceFila] ?? []));
                $tieneContenido = false;

                foreach ($candidata as $valor) {
                    if (trim((string) $valor) !== '') {
                        $tieneContenido = true;
                        break;
                    }
                }

                if (!$tieneContenido) {
                    continue;
                }

                $evaluacion = $this->evaluarFilaEncabezado($candidata);
                $evaluacion['indice_fila'] = $indiceFila;
                $filasEvaluadas[] = [
                    'indice_fila' => $indiceFila,
                    'porcentaje_coincidencia' => $evaluacion['validacion']['porcentaje_coincidencia'],
                    'columnas_reconocidas' => $evaluacion['validacion']['columnas_reconocidas'],
                    'valido' => $evaluacion['validacion']['valido'],
                ];

                if (
                    $mejorFila === null
                    || $evaluacion['puntaje'] > $mejorFila['puntaje']
                    || (
                        $evaluacion['puntaje'] === $mejorFila['puntaje']
                        && $evaluacion['validacion']['columnas_reconocidas'] > $mejorFila['validacion']['columnas_reconocidas']
                    )
                ) {
                    $mejorFila = $evaluacion;
                }
            }

            if ($mejorFila === null) {
                $diagnosticos[] = [
                    'hoja' => $sheetName,
                    'filas_detectadas' => $cantidadFilas,
                    'columnas_detectadas' => $this->obtenerTotalColumnas($rows),
                    'encabezados_leidos' => [],
                    'encabezados_normalizados' => [],
                    'encabezados_esperados' => array_map(static fn (array $entrada): string => (string) ($entrada['original'] ?? ''), $encabezadosEsperados),
                    'encabezados_esperados_normalizados' => $encabezadosEsperadosNormalizados,
                    'filas_candidatas_evaluadas' => $filasEvaluadas,
                    'diferencias' => [
                        'faltantes' => $encabezadosEsperadosNormalizados,
                        'no_esperados' => [],
                    ],
                    'linea_invalido' => __FILE__ . ':' . (__LINE__ - 11),
                    'motivo_descartado' => 'No se encontraron filas candidatas para encabezados.',
                ];
                continue;
            }

            $headersLeidos = $mejorFila['headers_leidos'];
            $resultadoMapeo = $mejorFila['resultado_mapeo'];
            $validacion = $mejorFila['validacion'];

            $diagnosticos[] = [
                'hoja' => $sheetName,
                'filas_detectadas' => $cantidadFilas,
                'columnas_detectadas' => $this->obtenerTotalColumnas($rows) ?: count($headersLeidos),
                'fila_encabezado_detectada' => $mejorFila['indice_fila'],
                'filas_candidatas_evaluadas' => $filasEvaluadas,
                'encabezados_leidos' => $headersLeidos,
                'encabezados_normalizados' => $resultadoMapeo['encabezados_normalizados'],
                'encabezados_esperados' => array_map(static fn (array $entrada): string => (string) ($entrada['original'] ?? ''), $encabezadosEsperados),
                'encabezados_esperados_normalizados' => $encabezadosEsperadosNormalizados,
                'encabezados_reconocidos' => $validacion['columnas_encontradas'],
                'encabezados_desconocidos' => $validacion['columnas_desconocidas'],
                'columnas_faltantes' => $validacion['columnas_faltantes'],
                'diferencias' => [
                    'faltantes' => array_values(array_diff($encabezadosEsperadosNormalizados, $resultadoMapeo['encabezados_normalizados'])),
                    'no_esperados' => array_values(array_diff($resultadoMapeo['encabezados_normalizados'], $encabezadosEsperadosNormalizados)),
                ],
                'porcentaje_coincidencia' => $validacion['porcentaje_coincidencia'],
                'estado' => $validacion['estado'],
                'linea_invalido' => $validacion['valido'] ? null : (__FILE__ . ':' . (__LINE__ + 3)),
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
                    'header_row_index' => $mejorFila['indice_fila'],
                    'headers_originales' => $headersLeidos,
                    'mapa_columnas' => $resultadoMapeo['mapa_columnas'],
                    'validacion' => $validacion,
                ];
            }
        }

        $this->registrarDiagnosticoDeteccion([
            'archivo' => $contextoArchivo['archivo'],
            'mime_detectado' => $contextoArchivo['mime_detectado'],
            'hoja_seleccionada' => $mejorCoincidencia['sheet_name'] ?? null,
            'diagnostico' => $diagnosticos,
            'linea_invalido' => ($mejorCoincidencia === null || !$mejorCoincidencia['validacion']['valido'])
                ? (__FILE__ . ':' . (__LINE__ + 7))
                : null,
            'motivo_rechazo' => ($mejorCoincidencia === null || !$mejorCoincidencia['validacion']['valido'])
                ? 'No se encontró una hoja que cumpla el umbral de coincidencia del catálogo.'
                : null,
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

        $contextoArchivo = $this->obtenerContextoArchivo($rutaArchivo);

        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $deteccion = $this->detectarHojaInventario($spreadsheet, $contextoArchivo);

            if ($deteccion === null) {
                throw new \RuntimeException('No se encontró un inventario válido de Ferromex en el archivo Excel.');
            }

            $rows = $deteccion['sheet']->toArray();

            if (empty($rows)) {
                return [];
            }

            $inicioDatos = ((int) ($deteccion['header_row_index'] ?? 0)) + 1;
            $rows = array_slice($rows, $inicioDatos);

            return $this->leerRegistrosInventario($rows, $deteccion['mapa_columnas']);
        } catch (\Throwable $e) {
            $this->registrarDiagnosticoDeteccion([
                'archivo' => $contextoArchivo['archivo'],
                'mime_detectado' => $contextoArchivo['mime_detectado'],
                'hoja_seleccionada' => null,
                'linea_invalido' => __FILE__ . ':' . (__LINE__ - 3),
                'motivo_rechazo' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
