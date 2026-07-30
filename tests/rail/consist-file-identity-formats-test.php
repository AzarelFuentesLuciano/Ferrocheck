<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$previewer = new ConsistSpreadsheetPreviewer($configuration);
$passed = 0;
$failed = 0;
$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try { $ok = $assertion() === true; } catch (Throwable $e) { $ok = false; $label .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$rows = [
    'vehicle_load_report' => [
        ['fdWholeVIN', 'fdTransportationName1', 'fdLoadId', 'fdTrack', 'fdDestinationLocation'],
        ['V1', 'tren', 'carga', 'vía', 'destino'],
    ],
    'shippers' => [
        ['fdWholeVIN1', 'fdPedimentoType', 'fdPortCode', 'fdBillNumber', 'fdBillDate'],
        ['S1', 'tipo', 'puerto', 'factura', '2026-01-01'],
    ],
    'cnacs' => [
        ['VIN', 'InvoiceNo', 'NumRemesa', 'BrokerId', 'H/SCODE'],
        ['C1', 'factura', 'remesa', 'broker', 'hs'],
    ],
];
$fixtures = [];
foreach (['Xlsx' => Xlsx::class, 'Xls' => Xls::class, 'Csv' => Csv::class] as $readerType => $writerClass) {
    $batch = [];
    foreach ($rows as $field => $data) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([['Introducción'], ...$data]);
        $path = tempnam(sys_get_temp_dir(), 'consist_identity_') . '.' . strtolower($readerType);
        (new $writerClass($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $fixtures[] = $path;
        $batch[$field] = [
            'field' => $field,
            'label' => $configuration['files'][$field]['label'],
            'path' => $path,
            'original_name' => $field . '.' . strtolower($readerType),
            'extension' => strtolower($readerType),
            'mime' => 'test/fixture',
            'reader_type' => $readerType,
            'size' => filesize($path),
        ];
    }
    $preview = $previewer->previewBatch($batch);
    $test("{$readerType}: valida identidad y VIN en los tres archivos", static fn (): bool =>
        $preview['valid'] === true
        && count(array_filter($preview['files'], static fn (array $file): bool => $file['valid'] === true)) === 3
    );
}

foreach ($fixtures as $path) {
    unlink($path);
}
echo "\nResumen Consist Identity Formats: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
