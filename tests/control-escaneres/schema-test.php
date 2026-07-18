<?php

declare(strict_types=1);

/**
 * Validación estática: no abre conexiones y no ejecuta SQL.
 * Uso: php tests/control-escaneres/schema-test.php
 */

$root = dirname(__DIR__, 2);
$migrationDir = $root . '/database/migrations';
$expected = [
    '20260718_001_create_control_escaneres_scanners.sql' => 'scanners',
    '20260718_002_create_scanner_movimientos.sql' => 'scanner_movimientos',
    '20260718_003_create_scanner_inspecciones.sql' => 'scanner_inspecciones',
    '20260718_004_create_scanner_inspeccion_detalles.sql' => 'scanner_inspeccion_detalles',
    '20260718_005_create_scanner_incidencias.sql' => 'scanner_incidencias',
    '20260718_006_create_scanner_evidencias.sql' => 'scanner_evidencias',
    '20260718_007_create_auditoria_eventos.sql' => 'auditoria_eventos',
];

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
}

function readSql(string $path): string
{
    $content = file_get_contents($path);
    return is_string($content) ? $content : '';
}

$sqlByTable = [];
foreach ($expected as $file => $table) {
    $path = $migrationDir . '/' . $file;
    check("Existe {$file}", is_file($path));
    $sql = is_file($path) ? readSql($path) : '';
    $sqlByTable[$table] = $sql;
    check("{$table}: CREATE TABLE", (bool) preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+' . preg_quote($table, '/') . '\s*\(/i', $sql));
    check("{$table}: PRIMARY KEY", stripos($sql, 'PRIMARY KEY') !== false);
    check("{$table}: timestamps", stripos($sql, 'created_at') !== false);
    check("{$table}: rollback documentado", stripos($sql, 'ROLLBACK') !== false && stripos($sql, 'DROP TABLE IF EXISTS') !== false);
    check("{$table}: paréntesis balanceados", substr_count($sql, '(') === substr_count($sql, ')'));
}

$allSql = implode("\n", $sqlByTable);
$orderedFiles = array_keys($expected);
$sortedFiles = $orderedFiles;
sort($sortedFiles, SORT_STRING);

check('Orden cronológico de dependencias', $orderedFiles === $sortedFiles);
check('Siete tablas canónicas declaradas', count($sqlByTable) === 7);
check('No contiene columna PIN', !preg_match('/\bpin\s+(?:VAR)?CHAR\b/i', $allSql));
check('No contiene columna PUK', !preg_match('/\bpuk\s+(?:VAR)?CHAR\b/i', $allSql));
check('Convención única SC-0001', str_contains($sqlByTable['scanners'], "^SC-[0-9]{4,}$"));
check('Estados de scanner completos', preg_match_all("/'(?:disponible|entregado|mantenimiento|pendiente_reparacion|baja_definitiva|extraviado)'/", $sqlByTable['scanners']) >= 6);
check('Código, QR, serie, IMEI e ICCID únicos', preg_match_all('/CONSTRAINT\s+uq_ce_scanners_/i', $sqlByTable['scanners']) >= 5);
check('Conservación limitada a 0–100', (bool) preg_match('/indice_conservacion\s+BETWEEN\s+0\s+AND\s+100/i', $sqlByTable['scanners']));
check('Movimiento abierto usa columna generada nullable', str_contains($sqlByTable['scanner_movimientos'], "CASE WHEN estado = 'abierto' THEN scanner_id ELSE NULL END"));
check('Movimiento abierto tiene UNIQUE', stripos($sqlByTable['scanner_movimientos'], 'uq_ce_movimientos_abierto UNIQUE') !== false);
check('Folio es único', stripos($sqlByTable['scanner_movimientos'], 'uq_ce_movimientos_folio UNIQUE') !== false);
check('Inspección única por movimiento y tipo', stripos($sqlByTable['scanner_inspecciones'], 'uq_ce_inspecciones_movimiento_tipo UNIQUE') !== false);
check('Detalle único por inspección y componente', stripos($sqlByTable['scanner_inspeccion_detalles'], 'uq_ce_inspeccion_detalles_componente UNIQUE') !== false);
check('Evidencias no contienen columna binaria', !preg_match('/\b(?:BLOB|LONGBLOB|MEDIUMBLOB|VARBINARY)\b/i', $sqlByTable['scanner_evidencias']));
check('Evidencias incluyen ruta, MIME, tamaño y hash', preg_match_all('/\b(?:ruta_storage|mime_type|tamano_bytes|hash_sha256)\b/i', $sqlByTable['scanner_evidencias']) >= 4);
check('Incidencias tienen estados y severidades', stripos($sqlByTable['scanner_incidencias'], 'en_mantenimiento') !== false && stripos($sqlByTable['scanner_incidencias'], 'critica') !== false);
check('Auditoría se declara append-only', stripos($sqlByTable['auditoria_eventos'], 'append-only') !== false);
check('Auditoría no define UPDATE', !preg_match('/^\s*UPDATE\s+/mi', $sqlByTable['auditoria_eventos']));
check('Auditoría no define DELETE', !preg_match('/^\s*DELETE\s+/mi', $sqlByTable['auditoria_eventos']));
check('Actores futuros permanecen nullable', preg_match_all('/(?:created_by|updated_by|registrada_por|usuario_id)\s+BIGINT\s+UNSIGNED\s+NULL/i', $allSql) >= 4);
check('Compatibilidad MariaDB usa PERSISTENT', stripos($sqlByTable['scanner_movimientos'], 'PERSISTENT') !== false);
check('Incidencias conservan movement_id', preg_match('/movimiento_id\s+BIGINT\s+UNSIGNED\s+NULL/i', $sqlByTable['scanner_incidencias']) === 1);
$manifestPath = $root . '/database/migrations/control-escaneres/manifest.json';
$manifestData = json_decode((string) file_get_contents($manifestPath), true);
check('Manifest declara siete migraciones', count($manifestData['migrations'] ?? []) === 7);
check('Manifest sin timestamps dinámicos', !str_contains((string) file_get_contents($manifestPath), 'generated_at'));

$ferrocheckStatus = trim((string) shell_exec('git status --short -- app/Views/inventario public/assets/js/importador.js public/assets/css/importador.css 2>NUL'));
check('FerroCheck sin modificaciones', $ferrocheckStatus === '');

echo "\nResumen Schema Control de Escáneres: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
