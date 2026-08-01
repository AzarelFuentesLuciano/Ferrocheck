<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Repositories\Rail\ConsistRepository;
use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistUnitDataNormalizer;
use App\Services\Rail\Consist\ConsistWorkbookExporter;
use PhpOffice\PhpSpreadsheet\IOFactory;

$root = dirname(__DIR__, 2);
$reference = $root . '/docs/rail/consist/referencias/Consist Rail del 29 al 30 de Julio de 2026.xlsx';
$reader = IOFactory::createReaderForFile($reference);
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['Consist']);
$sourceBook = $reader->load($reference);
$sourceSheet = $sourceBook->getSheetByName('Consist');
$headers = ConsistDocumentBuilder::HEADERS;
$known = [];
foreach ([2, 10, 18] as $rowNumber) {
    $row = [];
    foreach ($headers as $index => $header) {
        $row[$header] = $sourceSheet->getCell([$index + 1, $rowNumber])->getFormattedValue();
    }
    $known[] = $row;
}
$sourceBook->disconnectWorksheets();

$normalizer = new ConsistUnitDataNormalizer();
foreach ($known as $row) {
    $platform = (string) $row['fdTransportationName1'];
    assert($platform !== '');
    assert($normalizer->finalData($row)['fdTransportationName1'] === $platform);
    assert($normalizer->finalData(array_values($row))['fdTransportationName1'] === $platform);
    $withoutPlatform = $row;
    $withoutPlatform['fdTransportationName1'] = '';
    assert($normalizer->finalData($withoutPlatform, [
        'FDTRANSPORTATIONNAME1' => $platform,
    ])['fdTransportationName1'] === $platform);
}

$repository = new ConsistRepository(new PDO('sqlite::memory:'));
$hydrate = new ReflectionMethod($repository, 'hydrateUnit');
$persistedEquivalent = $hydrate->invoke($repository, [
    'vin' => $known[0]['fdwholevin'],
    'final_data_json' => json_encode(array_values($known[0]), JSON_THROW_ON_ERROR),
    'vehicle_load_data_json' => json_encode([
        'fdtransportationname1' => $known[0]['fdTransportationName1'],
    ], JSON_THROW_ON_ERROR),
    'shippers_data_json' => null,
    'cnacs_selected_data_json' => null,
    'cnacs_additional_data_json' => null,
    'trace_json' => null,
    'issues_json' => null,
]);
assert($persistedEquivalent['platform_number'] === $known[0]['fdTransportationName1']);
assert($persistedEquivalent['final_data_json']['fdTransportationName1'] === $known[0]['fdTransportationName1']);

$directory = sys_get_temp_dir() . '/vascor-platform-pipeline-' . bin2hex(random_bytes(5));
$export = (new ConsistWorkbookExporter())->export([
    'fecha_inicio' => '2026-07-29',
    'fecha_fin' => '2026-07-30',
    'units' => [[
        'vin' => $known[0]['fdwholevin'],
        'final_data_json' => array_values($known[0]),
        'vehicle_load_data_json' => ['fdtransportationname1' => $known[0]['fdTransportationName1']],
    ]],
], $directory);
$book = IOFactory::load($export['path']);
assert($book->getSheetByName('Consist')->getCell('A2')->getValue() === $known[0]['fdTransportationName1']);
assert($book->getSheetByName('Summary')->getCell('A2')->getValue() === $known[0]['fdTransportationName1']);
assert((int) $book->getSheetByName('Summary')->getCell('G2')->getValue() === 1);
$book->disconnectWorksheets();
unlink($export['path']);
rmdir($directory);

echo "[PASS] Plataforma permanece idéntica en entrada, normalización, persistencia equivalente y XLSX\n";
