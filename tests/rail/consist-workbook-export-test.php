<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistOperationalPeriod;
use App\Services\Rail\Consist\ConsistWorkbookExporter;
use App\Services\Rail\Consist\ConsistWorkbookValidator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

$root = dirname(__DIR__, 2);
$reference = $root . '/docs/rail/consist/referencias/Consist Rail del 29 al 30 de Julio de 2026.xlsx';
$initialHash = hash_file('sha256', $reference);
$reader = IOFactory::createReaderForFile($reference);
$reader->setReadDataOnly(false);
$reader->setLoadSheetsOnly(['Consist', 'Summary', 'Version']);
$sourceBook = $reader->load($reference);
$sheet = $sourceBook->getSheetByName('Consist');
$units = [];
for ($row = 2; $row <= 438; $row++) {
    $final = [];
    foreach (ConsistDocumentBuilder::HEADERS as $index => $header) {
        $final[$header] = $sheet->getCell([$index + 1, $row])->getFormattedValue();
    }
    $units[] = ['vin' => (string) $final['fdwholevin'], 'final_data_json' => $final];
}
$units[0]['vin'] = '12345678901234567';
$units[0]['final_data_json']['fdwholevin'] = '12345678901234567';

$directory = sys_get_temp_dir() . '/vascor-consist-export-test-' . bin2hex(random_bytes(6));
$export = (new ConsistWorkbookExporter())->export([
    'fecha_inicio' => '2026-07-29',
    'fecha_fin' => '2026-07-30',
    'units' => $units,
    'operational_summary' => [
        'pending_platforms' => 7,
        'confirmed_platforms' => 48,
        'total_loaded_platforms' => 55,
    ],
], $directory);
$validation = (new ConsistWorkbookValidator())->validate($export['path']);

assert(count($units) === 437);
assert($export['filename'] === 'Consist Rail del 29 al 30 de Julio de 2026.xlsx');
assert($export['total_units'] === 437);
assert($export['total_summary_rows'] === 55);
assert($validation['sheets'] === ['Consist', 'Summary', 'Version']);
assert($validation['formulas'] === 0);
assert($validation['external_links'] === 0);
assert($validation['connections'] === 0);
assert($validation['macros'] === 0);
$outputReader = IOFactory::createReaderForFile($export['path']);
$output = $outputReader->load($export['path']);
assert($output->getSheetNames() === ['Consist', 'Summary', 'Version']);
assert($output->getSheetByName('Version')->getSheetState() === 'hidden');
assert($output->getSheetNames() === $sourceBook->getSheetNames());
assert($output->getSheetByName('Consist')->getTableByName('ConsistTable')?->getRange() === 'A1:N438');
assert($output->getSheetByName('Summary')->getTableByName('SummaryTable')?->getRange() === 'A1:G56');
assert($output->getSheetByName('Consist')->getColumnDimension('A')->getWidth() === 29.42578125);
assert($output->getSheetByName('Summary')->getColumnDimension('A')->getWidth() === 33.140625);
assert($output->getSheetByName('Summary')->getCell('I1')->getValue() === 'Row Labels');
assert($output->getSheetByName('Summary')->getCell('J1')->getValue() === 'Cuenta de fdTransportationName1');
assert($output->getSheetByName('Summary')->getColumnDimension('I')->getWidth() === 18.0);
assert($output->getSheetByName('Summary')->getColumnDimension('J')->getWidth() === 28.7109375);
assert(!in_array('Bloque', ConsistDocumentBuilder::HEADERS, true));
assert(!in_array('Bloque', ConsistWorkbookExporter::summaryHeaders(), true));
assert($output->getSheetByName('Consist')->getRowDimension(1)->getRowHeight() === 12.75);
assert($output->getSheetByName('Summary')->getRowDimension(1)->getRowHeight() === 15.0);
assert($output->getSheetByName('Consist')->getCell('B2')->getValue() === '12345678901234567');
assert($output->getSheetByName('Consist')->getCell('B2')->getDataType() === DataType::TYPE_STRING);
assert($output->getSheetByName('Consist')->getCell('C2')->getDataType() === DataType::TYPE_STRING);
assert($output->getSheetByName('Consist')->getCell('J2')->getDataType() === DataType::TYPE_STRING);

$summarySheet = $output->getSheetByName('Summary');
$platforms = [];
for ($row = 2; $row <= 56; $row++) {
    $platform = (string) $summarySheet->getCell("A{$row}")->getValue();
    assert($platform !== '');
    assert(!isset($platforms[$platform]));
    $platforms[$platform] = (int) $summarySheet->getCell("G{$row}")->getValue();
}
assert(count($platforms) === 55);
assert(array_sum($platforms) === 437);
assert(in_array(7, $platforms, true));
assert(in_array(8, $platforms, true));

$firstAppearance = [];
foreach ($units as $unit) {
    $platform = (string) $unit['final_data_json']['fdTransportationName1'];
    if (!isset($firstAppearance[$platform])) {
        $firstAppearance[$platform] = true;
    }
}
assert(array_keys($platforms) === array_keys($firstAppearance));

$destinationCounts = [];
$crossingCounts = [];
foreach (range(2, 56) as $row) {
    $destination = trim((string) $summarySheet->getCell("D{$row}")->getValue()) ?: '(en blanco)';
    $crossing = trim((string) $summarySheet->getCell("E{$row}")->getValue()) ?: '(en blanco)';
    $destinationCounts[$destination] = ($destinationCounts[$destination] ?? 0) + 1;
    $crossingCounts[$crossing] = ($crossingCounts[$crossing] ?? 0) + 1;
}
$readStaticSummary = static function ($sheet, int $startRow): array {
    $counts = [];
    for ($row = $startRow + 1; ; $row++) {
        $label = (string) $sheet->getCell("I{$row}")->getValue();
        if ($label === 'Total general') {
            return [$counts, (int) $sheet->getCell("J{$row}")->getValue(), $row];
        }
        $counts[$label] = (int) $sheet->getCell("J{$row}")->getValue();
    }
};
[$writtenDestinations, $destinationTotal, $destinationEnd] = $readStaticSummary($summarySheet, 1);
assert($writtenDestinations == $destinationCounts);
assert($destinationTotal === 55);
assert($summarySheet->getCell('I' . ($destinationEnd + 3))->getValue() === 'Row Labels');
[$writtenCrossings, $crossingTotal] = $readStaticSummary($summarySheet, $destinationEnd + 3);
if (!isset($crossingCounts['(en blanco)'])) {
    $crossingCounts = ['(en blanco)' => 0] + $crossingCounts;
}
assert($writtenCrossings == $crossingCounts);
assert($crossingTotal === 55);
assert($summarySheet->getCell('I61')->getValue() === 'Total general');
assert((int) $summarySheet->getCell('J61')->getValue() === 55);
foreach (['Plataformas Pendientes de Confirmar', 'Plataformas Confirmadas', 'Total de Plataformas Cargadas'] as $forbiddenMetric) {
    $summaryText = json_encode($summarySheet->toArray(null, true, true, false), JSON_UNESCAPED_UNICODE);
    assert(!str_contains((string) $summaryText, $forbiddenMetric));
}
assert($summarySheet->getStyle('I1')->getFont()->getName() === 'Calibri');
assert($summarySheet->getStyle('I1')->getFont()->getSize() === 10.0);
assert($summarySheet->getStyle('I2')->getAlignment()->getHorizontal() === 'left');
assert($summarySheet->getStyle('J2')->getAlignment()->getHorizontal() === 'center');
$versionSheet = $output->getSheetByName('Version');
assert($versionSheet->getColumnDimension('A')->getWidth() === 9.140625);
assert($versionSheet->getColumnDimension('B')->getWidth() === 68.0);
assert($versionSheet->getColumnDimension('C')->getWidth() === 11.85546875);
assert($versionSheet->getStyle('A1')->getFont()->getName() === 'Verdana');
assert($versionSheet->getStyle('A1')->getFont()->getSize() === 10.0);
assert($versionSheet->getStyle('A1')->getFont()->getBold());
foreach (['Consist' => range('A', 'N'), 'Summary' => range('A', 'G'), 'Version' => range('A', 'C')] as $sheetName => $columns) {
    foreach ($columns as $column) {
        assert($output->getSheetByName($sheetName)->getColumnDimension($column)->getWidth()
            === $sourceBook->getSheetByName($sheetName)->getColumnDimension($column)->getWidth());
    }
}
foreach (['Consist', 'Summary'] as $sheetName) {
    foreach (['A1', 'A2', 'G1', 'G2'] as $cell) {
        $actual = $output->getSheetByName($sheetName)->getStyle($cell);
        $expected = $sourceBook->getSheetByName($sheetName)->getStyle($cell);
        assert($actual->getFont()->getName() === $expected->getFont()->getName());
        assert($actual->getFont()->getSize() === $expected->getFont()->getSize());
        assert($actual->getFont()->getBold() === $expected->getFont()->getBold());
        assert($actual->getAlignment()->getHorizontal() === $expected->getAlignment()->getHorizontal());
        assert($actual->getAlignment()->getVertical() === $expected->getAlignment()->getVertical());
    }
}
$sourceBook->disconnectWorksheets();
$output->disconnectWorksheets();
assert(ConsistOperationalPeriod::fromInput('2026-07-30', '2026-07-30')->filename()
    === 'Consist Rail del 30 de Julio de 2026.xlsx');
assert(ConsistOperationalPeriod::fromInput('2026-07-31', '2026-08-01')->filename()
    === 'Consist Rail del 31 de Julio al 1 de Agosto de 2026.xlsx');
assert(ConsistOperationalPeriod::fromInput('2026-12-31', '2027-01-01')->filename()
    === 'Consist Rail del 31 de Diciembre de 2026 al 1 de Enero de 2027.xlsx');
assert(hash_file('sha256', $reference) === $initialHash);

$withDuplicate = $units;
$withDuplicate[] = $units[0];
$duplicateExport = (new ConsistWorkbookExporter())->export([
    'fecha_inicio' => '2026-07-29', 'fecha_fin' => '2026-07-30', 'units' => $withDuplicate,
], $directory);
$duplicateReader = IOFactory::createReaderForFile($duplicateExport['path']);
$duplicateReader->setReadDataOnly(true);
$duplicateBook = $duplicateReader->load($duplicateExport['path']);
assert((int) $duplicateBook->getSheetByName('Summary')->getCell('G2')->getValue() === 9);
assert($duplicateExport['total_summary_rows'] === 55);
$duplicateBook->disconnectWorksheets();

$variableCapacityUnits = [];
$offset = 0;
foreach (['PLATFORM-8' => 8, 'PLATFORM-7' => 7, 'PLATFORM-6' => 6] as $platform => $capacity) {
    for ($position = 1; $position <= $capacity; $position++) {
        $row = $units[$offset++]['final_data_json'];
        $row['fdTransportationName1'] = $platform;
        $row['fdwholevin'] = sprintf('TEST%013d', $offset);
        $variableCapacityUnits[] = ['vin' => $row['fdwholevin'], 'final_data_json' => $row];
    }
}
$capacityExport = (new ConsistWorkbookExporter())->export([
    'fecha_inicio' => '2026-07-29', 'fecha_fin' => '2026-07-30', 'units' => $variableCapacityUnits,
], $directory);
$capacityBook = IOFactory::load($capacityExport['path']);
$capacitySheet = $capacityBook->getSheetByName('Summary');
assert($capacityExport['total_summary_rows'] === 3);
assert((int) $capacitySheet->getCell('G2')->getValue() === 8);
assert((int) $capacitySheet->getCell('G3')->getValue() === 7);
assert((int) $capacitySheet->getCell('G4')->getValue() === 6);
$capacityBook->disconnectWorksheets();

$conflictingUnits = array_slice($variableCapacityUnits, 0, 2);
$conflictingUnits[1]['final_data_json']['Carrier'] = 'DIFFERENT';
$conflictingExport = (new ConsistWorkbookExporter())->export([
    'fecha_inicio' => '2026-07-29', 'fecha_fin' => '2026-07-30', 'units' => $conflictingUnits,
], $directory);
assert(count($conflictingExport['summary_warnings']) === 1);
assert($conflictingExport['summary_warnings'][0]['platform'] === 'PLATFORM-8');
assert($conflictingExport['summary_warnings'][0]['selected_rule'] === 'first_consist_row');
$conflictingBook = IOFactory::load($conflictingExport['path']);
assert($conflictingBook->getSheetByName('Summary')->getCell('B2')->getValue()
    === $conflictingUnits[0]['final_data_json']['Carrier']);
$conflictingBook->disconnectWorksheets();

$emptyPlatformUnits = [$variableCapacityUnits[0]];
$emptyPlatformUnits[0]['final_data_json']['fdTransportationName1'] = '   ';
$emptyPlatformRejected = false;
try {
    (new ConsistWorkbookExporter())->export([
        'fecha_inicio' => '2026-07-29', 'fecha_fin' => '2026-07-30', 'units' => $emptyPlatformUnits,
    ], $directory);
} catch (RuntimeException $exception) {
    $emptyPlatformRejected = str_contains($exception->getMessage(), 'sin plataforma');
}
assert($emptyPlatformRejected);

$keepGenerated = getenv('CONSIST_KEEP_GENERATED') === '1';
if ($keepGenerated) {
    echo "[INFO] Archivo generado para validación visual: {$export['path']}\n";
    unlink($duplicateExport['path']);
    unlink($capacityExport['path']);
    unlink($conflictingExport['path']);
} else {
    unlink($export['path']);
    unlink($duplicateExport['path']);
    unlink($capacityExport['path']);
    unlink($conflictingExport['path']);
    rmdir($directory);
}
echo "[PASS] Consist workbook export dinámico 437/437, Summary 55 y snapshot estático\n";
