<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$passed = 0;
$failed = 0;
$test = static function (string $name, callable $callback) use (&$passed, &$failed): void {
    try { $ok = $callback() === true; } catch (Throwable $e) { $ok = false; $name .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $name);
};
$path = tempnam(sys_get_temp_dir(), 'crp_') . '.csv';
file_put_contents($path, "\xEF\xBB\xBFReporte;;;;\nVehicle Identification Number;Dato\n abc123 ;uno\nABC123;dos\n;sin vin\n\n");
$meta = static fn (string $field): array => [
    'field' => $field, 'label' => $field, 'path' => $path, 'original_name' => $field . '.csv',
    'extension' => 'csv', 'mime' => 'text/plain', 'reader_type' => 'Csv', 'size' => filesize($path),
];
$previewer = new ConsistSpreadsheetPreviewer($configuration);
$preview = $previewer->previewBatch([
    'vehicle_load_report' => $meta('vehicle_load_report'),
    'shippers' => $meta('shippers'),
    'cnacs' => $meta('cnacs'),
]);
$file = $preview['files']['vehicle_load_report'];
$test('detecta encabezado real y alias VIN', static fn (): bool => $file['header_row'] === 2 && ($file['found_columns']['vin'] ?? '') === 'Vehicle Identification Number');
$test('normaliza VIN con trim y mayúsculas', static fn (): bool => ($file['sample'][0]['vin'] ?? '') === 'ABC123');
$test('detecta duplicado y VIN vacío', static fn (): bool => $file['duplicate_vin'] === 1 && $file['empty_vin'] === 1);
$test('cuenta registros válidos', static fn (): bool => $file['valid_records'] === 1);
$test('normaliza encabezados con acentos y separadores', static fn (): bool => $previewer->normalizeHeader("  Identificación_del VIN ") === 'identificacion del vin');
unlink($path);
echo "\nResumen Consist Spreadsheet Previewer: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
