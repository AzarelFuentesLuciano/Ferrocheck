<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PDO;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sends a JSON response and finishes execution.
 */
function responderJson(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Returns uploaded Excel file info or throws an exception.
 */
function obtenerArchivoSubido(): array
{
    if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    $archivo = $_FILES['archivo'];

    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    $rutaTemporal = (string)($archivo['tmp_name'] ?? '');
    if ($rutaTemporal === '' || !is_file($rutaTemporal)) {
        throw new RuntimeException('No se recibió ningún archivo.');
    }

    return $archivo;
}

/**
 * Validates file extension against allowed Excel formats.
 */
function validarExtensionArchivo(string $nombreArchivo): void
{
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    $permitidas = ['xlsx', 'xls'];

    if (!in_array($extension, $permitidas, true)) {
        throw new RuntimeException('La extensión del archivo no es válida. Solo se permiten .xlsx y .xls.');
    }
}

/**
 * Loads the official Ferromex catalog and returns entries in order.
 */
function cargarCatalogoColumnas(): array
{
    $rutaCatalogo = __DIR__ . '/../config/catalogo_columnas.php';

    if (!is_file($rutaCatalogo)) {
        throw new RuntimeException('No se encontró el catálogo de columnas.');
    }

    $catalogo = require $rutaCatalogo;

    if (!is_array($catalogo) || empty($catalogo)) {
        throw new RuntimeException('El catálogo de columnas es inválido o está vacío.');
    }

    $resultado = [];

    foreach ($catalogo as $entrada) {
        if (!is_array($entrada)) {
            continue;
        }

        $original = trim((string)($entrada['original'] ?? ''));
        $interno = trim((string)($entrada['internal'] ?? ''));

        if ($original === '' || $interno === '') {
            continue;
        }

        $resultado[] = ['original' => $original, 'internal' => $interno];
    }

    if (empty($resultado)) {
        throw new RuntimeException('El catálogo de columnas no contiene entradas válidas.');
    }

    return $resultado;
}

/**
 * Normalizes text for robust header comparison.
 */
function normalizarTexto(string $texto): string
{
    $valor = trim($texto);

    if ($valor === '') {
        return '';
    }

    $valor = mb_convert_encoding($valor, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    $transliterado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);

    if ($transliterado !== false) {
        $valor = $transliterado;
    }

    $valor = strtolower($valor);

    return preg_replace('/[^a-z0-9]+/', '', $valor) ?? '';
}

/**
 * Normalizes a single cell value according to import rules.
 */
function normalizarCelda(mixed $valor, string $nombreInterno): string
{
    $texto = trim((string)($valor ?? ''));
    $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

    if ($nombreInterno === 'equipo') {
        $texto = strtoupper($texto);
    }

    return $texto;
}

/**
 * Builds header normalization map from catalog entries.
 */
function construirMapaCatalogoPorEncabezado(array $catalogo): array
{
    $mapa = [];

    foreach ($catalogo as $entrada) {
        $clave = normalizarTexto($entrada['original']);
        if ($clave === '') {
            continue;
        }

        $mapa[$clave][] = $entrada['internal'];
    }

    return $mapa;
}

/**
 * Returns ordered unique internal columns from catalog.
 */
function obtenerColumnasInternasOrdenadas(array $catalogo): array
{
    $columnas = [];

    foreach ($catalogo as $entrada) {
        $interno = $entrada['internal'];
        if (!in_array($interno, $columnas, true)) {
            $columnas[] = $interno;
        }
    }

    return $columnas;
}

/**
 * Detects the most likely header row in the first worksheet.
 */
function detectarFilaEncabezados(array $filas, array $mapaCatalogo): int
{
    $mejorIndice = -1;
    $mejorScore = -1;
    $limite = min(count($filas), 30);

    for ($i = 0; $i < $limite; $i++) {
        $fila = $filas[$i] ?? [];
        $score = 0;

        foreach ($fila as $celda) {
            $normalizada = normalizarTexto((string)$celda);
            if ($normalizada !== '' && isset($mapaCatalogo[$normalizada])) {
                $score++;
            }
        }

        if ($score > $mejorScore) {
            $mejorScore = $score;
            $mejorIndice = $i;
        }
    }

    if ($mejorIndice < 0 || $mejorScore <= 0) {
        throw new RuntimeException('No se pudo detectar la fila de encabezados en la primera hoja.');
    }

    return $mejorIndice;
}

/**
 * Builds column index to internal-name map, handling duplicate official headers.
 */
function construirMapaColumnas(array $encabezados, array $mapaCatalogo): array
{
    $mapa = [];
    $ocurrencias = [];

    foreach ($encabezados as $indice => $encabezado) {
        $clave = normalizarTexto((string)$encabezado);

        if ($clave === '' || !isset($mapaCatalogo[$clave])) {
            continue;
        }

        $listaInternos = $mapaCatalogo[$clave];
        $n = $ocurrencias[$clave] ?? 0;
        $interno = $listaInternos[$n] ?? end($listaInternos);

        $ocurrencias[$clave] = $n + 1;
        $mapa[(int)$indice] = (string)$interno;
    }

    if (empty($mapa)) {
        throw new RuntimeException('No se pudo mapear ninguna columna del inventario.');
    }

    return $mapa;
}

/**
 * Reads and normalizes records from worksheet rows.
 */
function leerRegistros(array $filas, int $filaEncabezados, array $mapaColumnas, array $columnasInternas): array
{
    $registros = [];

    for ($i = $filaEncabezados + 1, $total = count($filas); $i < $total; $i++) {
        $fila = $filas[$i] ?? [];
        $registro = [];

        foreach ($columnasInternas as $columna) {
            $registro[$columna] = '';
        }

        foreach ($mapaColumnas as $indice => $columnaInterna) {
            $registro[$columnaInterna] = normalizarCelda($fila[$indice] ?? '', $columnaInterna);
        }

        $tieneContenido = false;
        foreach ($registro as $valor) {
            if ($valor !== '') {
                $tieneContenido = true;
                break;
            }
        }

        if ($tieneContenido) {
            $registros[] = $registro;
        }
    }

    return $registros;
}

/**
 * Inserts inventory records and updates import metadata in a single transaction.
 */
function ejecutarImportacion(PDO $pdo, array $registros, array $columnas, array $meta): int
{
    if (empty($registros)) {
        throw new RuntimeException('No se encontraron registros para importar.');
    }

    $listaColumnas = implode(', ', $columnas);
    $placeholders = implode(', ', array_map(static fn(string $c): string => ':' . $c, $columnas));
    $sqlInsert = 'INSERT INTO inventario (' . $listaColumnas . ') VALUES (' . $placeholders . ')';

    $sqlConfig = 'INSERT INTO configuracion (
            id,
            archivo_importado,
            total_registros,
            tamano_archivo,
            hash_archivo,
            fecha_importacion,
            fecha_actualizacion,
            version_inventario,
            tiempo_importacion
        ) VALUES (
            1,
            :archivo_importado,
            :total_registros,
            :tamano_archivo,
            :hash_archivo,
            NOW(),
            NOW(),
            1,
            :tiempo_importacion
        )
        ON DUPLICATE KEY UPDATE
            archivo_importado = VALUES(archivo_importado),
            total_registros = VALUES(total_registros),
            tamano_archivo = VALUES(tamano_archivo),
            hash_archivo = VALUES(hash_archivo),
            fecha_importacion = NOW(),
            fecha_actualizacion = NOW(),
            version_inventario = version_inventario + 1,
            tiempo_importacion = VALUES(tiempo_importacion)';

    $pdo->beginTransaction();

    try {
        $pdo->exec('TRUNCATE TABLE inventario');

        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($registros as $registro) {
            $params = [];
            foreach ($columnas as $columna) {
                $params[':' . $columna] = $registro[$columna] ?? '';
            }
            $stmtInsert->execute($params);
        }

        $stmtConfig = $pdo->prepare($sqlConfig);
        $stmtConfig->execute([
            ':archivo_importado' => $meta['archivo_importado'],
            ':total_registros' => count($registros),
            ':tamano_archivo' => $meta['tamano_archivo'],
            ':hash_archivo' => $meta['hash_archivo'],
            ':tiempo_importacion' => $meta['tiempo_importacion'],
        ]);

        $pdo->commit();

        return count($registros);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

try {
    $inicio = microtime(true);

    $archivo = obtenerArchivoSubido();
    $nombreArchivo = (string)$archivo['name'];
    $rutaTemporal = (string)$archivo['tmp_name'];
    $tamanoArchivo = (int)($archivo['size'] ?? 0);

    validarExtensionArchivo($nombreArchivo);

    $hashArchivo = hash_file('sha256', $rutaTemporal);
    if ($hashArchivo === false) {
        throw new RuntimeException('No se pudo calcular el hash del archivo.');
    }

    $catalogo = cargarCatalogoColumnas();
    $mapaCatalogo = construirMapaCatalogoPorEncabezado($catalogo);
    $columnasInternas = obtenerColumnasInternasOrdenadas($catalogo);

    $spreadsheet = IOFactory::load($rutaTemporal);
    $hoja = $spreadsheet->getSheet(0);

    if (!$hoja instanceof Worksheet) {
        throw new RuntimeException('No se pudo leer la primera hoja del archivo Excel.');
    }

    $filas = $hoja->toArray('', true, true, false);
    if (empty($filas)) {
        throw new RuntimeException('La primera hoja del Excel está vacía.');
    }

    $filaEncabezados = detectarFilaEncabezados($filas, $mapaCatalogo);
    $encabezados = $filas[$filaEncabezados] ?? [];
    $mapaColumnas = construirMapaColumnas($encabezados, $mapaCatalogo);
    $registros = leerRegistros($filas, $filaEncabezados, $mapaColumnas, $columnasInternas);

    $tiempoImportacion = round(microtime(true) - $inicio, 2);

    $pdo = crearConexionPDO();
    $total = ejecutarImportacion($pdo, $registros, $columnasInternas, [
        'archivo_importado' => $nombreArchivo,
        'tamano_archivo' => $tamanoArchivo,
        'hash_archivo' => $hashArchivo,
        'tiempo_importacion' => $tiempoImportacion,
    ]);

    responderJson([
        'success' => true,
        'message' => 'Inventario importado correctamente.',
        'registros' => $total,
        'archivo' => $nombreArchivo,
        'tiempo' => number_format($tiempoImportacion, 2, '.', ''),
    ]);
} catch (Throwable $e) {
    responderJson([
        'success' => false,
        'message' => $e->getMessage(),
    ], 400);
}
