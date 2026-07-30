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
$makeCsv = static function (string $content): string {
    $path = tempnam(sys_get_temp_dir(), 'crp_') . '.csv';
    file_put_contents($path, $content);
    return $path;
};
$paths = [
    'vehicle_load_report' => $makeCsv("\xEF\xBB\xBFReporte;;;;;\nfdWholeVIN;fdTransportationName1;fdLoadId;fdTrack;fdDestinationLocation;Dato\n abc123 ;tren;carga;vía;destino;uno\nABC123;tren;carga;vía;destino;dos\n;tren;carga;vía;destino;sin vin\n"),
    'shippers' => $makeCsv(" whole vin 1 ;fdPedimentoType;fdPortCode;fdBillNumber;fdBillDate;fdPrintDate\nSHIP1;tipo;puerto;factura;2026-01-01;2026-01-02\n"),
    'cnacs' => $makeCsv("VIN;InvoiceNo;NumRemesa;BrokerId;H/SCODE;BodyModel;Conveyance\nCNACS1;fac;rem;bro;hs;body;conv\n"),
];
$meta = static fn (string $field, string $path): array => [
    'field' => $field, 'label' => $field, 'path' => $path, 'original_name' => $field . '.csv',
    'extension' => 'csv', 'mime' => 'text/plain', 'reader_type' => 'Csv', 'size' => filesize($path),
];
$batch = [];
foreach ($paths as $field => $path) {
    $batch[$field] = $meta($field, $path);
}
$previewer = new ConsistSpreadsheetPreviewer($configuration);
$preview = $previewer->previewBatch($batch);
$file = $preview['files']['vehicle_load_report'];
$test('valida las tres identidades configuradas', static fn (): bool => $preview['valid'] === true);
$test('detecta encabezado real y alias VIN', static fn (): bool => $file['header_row'] === 2 && ($file['found_columns']['vin'] ?? '') === 'fdWholeVIN');
$test('normaliza VIN con trim y mayúsculas', static fn (): bool => ($file['sample'][0]['vin'] ?? '') === 'ABC123');
$test('detecta duplicado y VIN vacío', static fn (): bool => $file['duplicate_vin'] === 1 && $file['empty_vin'] === 1);
$test('cuenta registros válidos', static fn (): bool => $file['valid_records'] === 1);
$test('expone firma de identidad', static fn (): bool => $file['matched_identity_headers'] === 4 && $file['total_identity_headers'] === 5);
$test('normaliza encabezados con acentos, espacios y slash', static fn (): bool =>
    $previewer->normalizeHeader("  Identificación_del VIN ") === 'identificacion del vin'
    && $previewer->normalizeHeader(' H/SCODE ') === 'h/scode'
);

$withoutVin = $makeCsv("InvoiceNo;NumRemesa;BrokerId;H/SCODE\nfac;rem;bro;hs\n");
$invalidBatch = $batch;
$invalidBatch['cnacs'] = $meta('cnacs', $withoutVin);
$invalidPreview = $previewer->previewBatch($invalidBatch);
$test('rechaza archivo sin VIN', static fn (): bool =>
    $invalidPreview['valid'] === false
    && str_contains($invalidPreview['files']['cnacs']['errors'][0] ?? '', 'VIN')
);

$swappedBatch = $batch;
$swappedBatch['shippers'] = $meta('shippers', $paths['cnacs']);
$swappedPreview = $previewer->previewBatch($swappedBatch);
$test('rechaza CNACS cargado en Shippers con mensaje específico', static fn (): bool =>
    $swappedPreview['valid'] === false
    && $swappedPreview['files']['shippers']['detected_file_type'] === 'cnacs'
    && str_contains($swappedPreview['files']['shippers']['errors'][0] ?? '', 'parece ser un archivo “CNACS”')
);

foreach ([...array_values($paths), $withoutVin] as $path) {
    unlink($path);
}
echo "\nResumen Consist Spreadsheet Previewer: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
