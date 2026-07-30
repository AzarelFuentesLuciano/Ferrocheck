<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistVinExtractor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$extractor = new ConsistVinExtractor($configuration);
$definition = $configuration['files']['cnacs'];
$passed = 0;
$failed = 0;
$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try { $ok = $assertion() === true; } catch (Throwable $e) { $ok = false; $label .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$fixtures = [];
foreach (['Xlsx'=>Xlsx::class, 'Xls'=>Xls::class, 'Csv'=>Csv::class] as $type => $writerClass) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['Reporte introductorio'],
        ['Fecha', 'sin encabezado'],
        ['VIN', 'Dato'],
        [' abc123 ', 'uno'],
        ['ABC123', 'duplicado'],
        ['', 'sin vin'],
        ['vin002', 'dos'],
    ]);
    $extension = strtolower($type);
    $path = tempnam(sys_get_temp_dir(), 'vin_extract_') . '.' . $extension;
    (new $writerClass($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();
    $fixtures[$type] = $path;
}

foreach ($fixtures as $type => $path) {
    $result = $extractor->extract(['path'=>$path,'reader_type'=>$type], $definition);
    $test("{$type}: detecta encabezado desplazado", static fn (): bool => $result['header_row'] === 3);
    $test("{$type}: normaliza y devuelve VIN únicos", static fn (): bool => array_keys($result['vins']) === ['ABC123', 'VIN002']);
    $test("{$type}: detecta duplicados", static fn (): bool => $result['duplicate_count'] === 1 && isset($result['duplicates']['ABC123']));
    $test("{$type}: cuenta VIN vacíos", static fn (): bool => $result['empty_vin'] === 1);
    $test("{$type}: conserva registros con VIN", static fn (): bool => $result['valid_records'] === 3);
}

$missingPath = tempnam(sys_get_temp_dir(), 'vin_missing_') . '.csv';
file_put_contents($missingPath, "Codigo,Otro\n1,2\n");
$test('rechaza ausencia de columna VIN', static function () use ($extractor, $missingPath, $definition): bool {
    try { $extractor->extract(['path'=>$missingPath,'reader_type'=>'Csv'], $definition); } catch (RuntimeException) { return true; }
    return false;
});
$source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Services/Rail/Consist/ConsistVinExtractor.php');
$test('no usa toArray sobre hojas completas', static fn (): bool => !str_contains($source, 'toArray('));

foreach ($fixtures as $path) { unlink($path); }
unlink($missingPath);
echo "\nResumen Consist VIN Extractor: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
