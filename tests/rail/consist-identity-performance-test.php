<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistHeaderReadFilter;
use App\Services\Rail\Consist\ConsistSpreadsheetIdentityValidator;
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistUploadValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$baseConfiguration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$passed = 0;
$failed = 0;
$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try { $ok = $assertion() === true; } catch (Throwable $e) { $ok = false; $label .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$csvMeta = static fn (string $path): array => [
    'path' => $path,
    'reader_type' => 'Csv',
    'original_name' => basename($path),
    'extension' => 'csv',
    'mime' => 'text/plain',
    'size' => filesize($path),
];
$temporaryFiles = [];

$limitedConfiguration = $baseConfiguration;
$limitedConfiguration['header_scan_rows'] = 5;
$limitedConfiguration['header_scan_max_columns'] = 4;
$limitedConfiguration['files']['cnacs']['identity_headers'] = ['a', 'b', 'c'];
$limitedConfiguration['files']['cnacs']['minimum_identity_matches'] = 3;
$limitedValidator = new ConsistSpreadsheetIdentityValidator($limitedConfiguration);

$lateHeader = tempnam(sys_get_temp_dir(), 'consist_late_');
$temporaryFiles[] = $lateHeader;
file_put_contents($lateHeader, "nota\nnota\nnota\nnota\nnota\nVIN,a,b,c\nV1,1,2,3\n");
$test('identidad no procesa filas posteriores a header_scan_rows', static function () use (
    $limitedValidator,
    $csvMeta,
    $lateHeader,
    $limitedConfiguration,
): bool {
    try {
        $limitedValidator->validateFile('cnacs', $csvMeta($lateHeader), $limitedConfiguration['files']['cnacs']);
    } catch (ConsistUploadValidationException $exception) {
        return str_contains($exception->getMessage(), 'primeras 5 filas');
    }
    return false;
});

$lateColumn = tempnam(sys_get_temp_dir(), 'consist_column_');
$temporaryFiles[] = $lateColumn;
file_put_contents($lateColumn, "VIN,a,b,aux,c\nV1,1,2,x,3\n");
$test('identidad limita columnas a header_scan_max_columns', static function () use (
    $limitedValidator,
    $csvMeta,
    $lateColumn,
    $limitedConfiguration,
): bool {
    try {
        $limitedValidator->validateFile('cnacs', $csvMeta($lateColumn), $limitedConfiguration['files']['cnacs']);
    } catch (ConsistUploadValidationException $exception) {
        return str_contains($exception->getMessage(), 'estructura reconocida');
    }
    return false;
});

$filter = new ConsistHeaderReadFilter(5, 4);
$test('ReadFilter bloquea filas y columnas fuera del límite', static fn (): bool =>
    $filter->readCell('A', 1)
    && $filter->readCell('D', 5)
    && !$filter->readCell('E', 1)
    && !$filter->readCell('A', 6)
);

$largeCsv = tempnam(sys_get_temp_dir(), 'consist_large_');
$temporaryFiles[] = $largeCsv;
$handle = fopen($largeCsv, 'wb');
fwrite($handle, "VIN,InvoiceNo,NumRemesa,BrokerId\n");
for ($row = 1; $row <= 20000; $row++) {
    fwrite($handle, "VIN{$row},I{$row},R{$row},B{$row}\n");
}
fclose($handle);
$validator = new ConsistSpreadsheetIdentityValidator($baseConfiguration);
$largeCsvResult = $validator->validateFile(
    'cnacs',
    $csvMeta($largeCsv),
    $baseConfiguration['files']['cnacs'],
);
$test('CSV grande valida identidad únicamente en encabezado temprano', static fn (): bool =>
    $largeCsvResult['row'] === 1
    && $largeCsvResult['detection']['is_valid'] === true
);

$inflatedPath = tempnam(sys_get_temp_dir(), 'consist_inflated_') . '.xlsx';
$temporaryFiles[] = $inflatedPath;
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray([['VIN', 'InvoiceNo', 'NumRemesa', 'BrokerId']]);
$sheet->setCellValue('XFD1000000', 'marcador inflado');
(new Xlsx($spreadsheet))->save($inflatedPath);
$spreadsheet->disconnectWorksheets();
$inflatedResult = $validator->validateFile(
    'cnacs',
    [
        'path' => $inflatedPath,
        'reader_type' => 'Xlsx',
        'original_name' => 'inflated.xlsx',
        'extension' => 'xlsx',
        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'size' => filesize($inflatedPath),
    ],
    $baseConfiguration['files']['cnacs'],
);
$test('XLSX con dimensiones infladas no recorre columna o fila extrema durante identidad', static fn (): bool =>
    $inflatedResult['row'] === 1
    && count($inflatedResult['headers']) === $baseConfiguration['header_scan_max_columns']
);

$vehiclePath = tempnam(sys_get_temp_dir(), 'consist_vehicle_');
$shipperPath = tempnam(sys_get_temp_dir(), 'consist_shipper_');
$temporaryFiles[] = $vehiclePath;
$temporaryFiles[] = $shipperPath;
file_put_contents($vehiclePath, "fdWholeVIN,fdTransportationName1,fdLoadId,fdTrack,fdDestinationLocation\nV1,1,2,3,4\n");
file_put_contents($shipperPath, "fdWholeVIN1,fdPedimentoType,fdPortCode,fdBillNumber,fdBillDate\nS1,1,2,3,4\n");
$inflatedBatch = [
    'vehicle_load_report' => $csvMeta($vehiclePath) + ['original_name'=>'vehicle.csv','extension'=>'csv','mime'=>'text/plain','size'=>filesize($vehiclePath)],
    'shippers' => $csvMeta($shipperPath) + ['original_name'=>'shippers.csv','extension'=>'csv','mime'=>'text/plain','size'=>filesize($shipperPath)],
    'cnacs' => [
        'path' => $inflatedPath,
        'reader_type' => 'Xlsx',
        'original_name' => 'inflated.xlsx',
        'extension' => 'xlsx',
        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'size' => filesize($inflatedPath),
    ],
];
$inflatedIdentities = $validator->validateBatch($inflatedBatch);
$test('vista previa rechaza dimensiones infladas antes de recorrer datos', static function () use (
    $baseConfiguration,
    $inflatedBatch,
    $inflatedIdentities,
): bool {
    try {
        (new ConsistSpreadsheetPreviewer($baseConfiguration))->previewBatch($inflatedBatch, $inflatedIdentities);
    } catch (ConsistUploadValidationException $exception) {
        return str_contains($exception->getMessage(), 'dimensiones');
    }
    return false;
});

$source = (string) file_get_contents(
    dirname(__DIR__, 2) . '/app/Services/Rail/Consist/ConsistSpreadsheetIdentityValidator.php',
);
$test('identidad usa primera hoja y filtro antes de load', static fn (): bool =>
    str_contains($source, 'listWorksheetNames')
    && !str_contains($source, 'listWorksheetInfo')
    && strpos($source, 'setReadFilter') < strpos($source, '$reader->load')
);
$test('CSV usa lectura secuencial acotada sin cargar la hoja completa', static fn (): bool =>
    str_contains($source, 'fgetcsv')
    && str_contains($source, '$row <= $this->scanRows()')
    && str_contains($source, 'fclose($handle)')
);

foreach ($temporaryFiles as $path) {
    if (is_file($path)) {
        unlink($path);
    }
}
echo "\nResumen Consist Identity Performance: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
