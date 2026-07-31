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
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['Consist']);
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
$sourceBook->disconnectWorksheets();
$units[0]['vin'] = '12345678901234567';
$units[0]['final_data_json']['fdwholevin'] = '12345678901234567';

$directory = sys_get_temp_dir() . '/vascor-consist-export-test-' . bin2hex(random_bytes(6));
$export = (new ConsistWorkbookExporter($reference))->export([
    'fecha_inicio' => '2026-07-29',
    'fecha_fin' => '2026-07-30',
    'units' => $units,
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
assert($output->getSheetByName('Consist')->getTableByName('ConsistTable')?->getRange() === 'A1:N438');
assert($output->getSheetByName('Summary')->getTableByName('SummaryTable')?->getRange() === 'A1:G56');
assert($output->getSheetByName('Consist')->getColumnDimension('A')->getWidth() === 29.42578125);
assert($output->getSheetByName('Summary')->getColumnDimension('A')->getWidth() === 33.140625);
assert($output->getSheetByName('Consist')->getRowDimension(1)->getRowHeight() === 12.75);
assert($output->getSheetByName('Summary')->getRowDimension(1)->getRowHeight() === 15.0);
assert($output->getSheetByName('Consist')->getCell('B2')->getValue() === '12345678901234567');
assert($output->getSheetByName('Consist')->getCell('B2')->getDataType() === DataType::TYPE_STRING);
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
$duplicateExport = (new ConsistWorkbookExporter($reference))->export([
    'fecha_inicio' => '2026-07-29', 'fecha_fin' => '2026-07-30', 'units' => $withDuplicate,
], $directory);
$duplicateReader = IOFactory::createReaderForFile($duplicateExport['path']);
$duplicateReader->setReadDataOnly(true);
$duplicateBook = $duplicateReader->load($duplicateExport['path']);
assert((int) $duplicateBook->getSheetByName('Summary')->getCell('G2')->getValue() === 7);
$duplicateBook->disconnectWorksheets();

unlink($export['path']);
unlink($duplicateExport['path']);
rmdir($directory);
echo "[PASS] Consist workbook export dinámico 437/437, Summary 55 y snapshot estático\n";
