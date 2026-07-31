<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistGoldenMasterComparator;
use App\Services\Rail\Consist\ConsistOperationalPeriod;
use App\Services\Rail\Consist\RouteCodeCatalogLoader;
use PhpOffice\PhpSpreadsheet\IOFactory;

$root = dirname(__DIR__, 2);
$template = $root . '/docs/rail/consist/referencias/Plantilla_Consist.xlsm';
$master = $root . '/docs/rail/consist/referencias/vascor_sm_db.xlsx';
$reader = IOFactory::createReaderForFile($template);
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['  Vehicle Load Report', 'Shippers', 'CNACS']);
$spreadsheet = $reader->load($template);

$records = static function (string $sheetName, string $vinHeader) use ($spreadsheet): array {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $headers = [];
    foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
        $headers[$cell->getColumn()] = trim((string) $cell->getFormattedValue());
    }
    $vinColumn = array_search($vinHeader, $headers, true);
    $result = [];
    for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
        $vin = mb_strtoupper(trim((string) $sheet->getCell($vinColumn . $row)->getFormattedValue()), 'UTF-8');
        if ($vin === '') {
            continue;
        }
        $data = [];
        foreach ($headers as $column => $header) {
            $value = trim((string) $sheet->getCell($column . $row)->getFormattedValue());
            if ($header !== '' && $value !== '') {
                $data[$header] = $value;
            }
        }
        $result[$vin][] = ['row' => $row, 'data' => $data];
    }
    return $result;
};

$vehicle = $records('  Vehicle Load Report', 'fdwholevin');
$shippers = $records('Shippers', 'fdWholeVIN1');
$cnacs = $records('CNACS', 'Vin');
$analysis = ['units' => []];
foreach ($vehicle as $vin => $vehicleRows) {
    $analysis['units'][] = [
        'vin' => $vin,
        'presence' => ['vehicle_load_report' => true, 'shippers' => isset($shippers[$vin]), 'cnacs' => isset($cnacs[$vin])],
        'duplicate_counts' => [
            'vehicle_load_report' => count($vehicleRows) - 1,
            'shippers' => count($shippers[$vin] ?? []) - 1,
            'cnacs' => count($cnacs[$vin] ?? []) - 1,
        ],
        'source_rows' => [
            'vehicle_load_report' => $vehicleRows[0]['row'],
            'shippers' => $shippers[$vin][0]['row'],
            'cnacs' => $cnacs[$vin][0]['row'],
        ],
        'source_data' => [
            'vehicle_load_report' => $vehicleRows[0]['data'],
            'shippers' => $shippers[$vin][0]['data'],
            'cnacs' => $cnacs[$vin][0]['data'],
            'cnacs_additional' => array_slice($cnacs[$vin], 1),
        ],
    ];
}
$orderPayload = json_decode((string) file_get_contents($root . '/docs/rail/consist/golden-master/vin-order.json'), true);
$order = [];
foreach ($orderPayload['rows'] as $row) {
    $order[$row['vin']] = $row['consist'];
}
$catalog = (new RouteCodeCatalogLoader($master))->load();
$period = ConsistOperationalPeriod::fromInput('2026-07-29', '2026-07-30');
$draft = (new ConsistDocumentBuilder($order))->build(str_repeat('a', 64), $analysis, $catalog, 1, $period);
$comparison = (new ConsistGoldenMasterComparator(
    $root . '/docs/rail/consist/golden-master/consist-output.json',
))->compare($draft);
$result = $draft->toArray();

assert($catalog['version']['record_count'] === 436);
assert($result['total_units'] === 624);
assert($result['total_platforms'] === 78);
assert($result['cnacs_duplicate_count'] === 24);
assert(count(array_filter($result['platforms'], static fn (array $platform): bool => $platform['total_units'] !== 8)) === 0);
assert($comparison['pass'] === true);
assert($comparison['matches'] === 624);
assert($comparison['differences'] === 0);
assert((static function () use ($analysis, $catalog): bool {
    try {
        (new ConsistDocumentBuilder())->build('token-invalido', $analysis, $catalog, 1, ConsistOperationalPeriod::fromInput('2026-07-29', '2026-07-30'));
    } catch (DomainException) {
        return true;
    }
    return false;
})());
$alien = $analysis;
$alien['units'][0]['presence']['vehicle_load_report'] = false;
assert((static function () use ($alien, $catalog): bool {
    try {
        (new ConsistDocumentBuilder())->build(str_repeat('b', 64), $alien, $catalog, 1, ConsistOperationalPeriod::fromInput('2026-07-29', '2026-07-30'));
    } catch (DomainException) {
        return true;
    }
    return false;
})());
$tieUnits = [$analysis['units'][1], $analysis['units'][0]];
$tieUnits[0]['source_data']['vehicle_load_report']['fdTrack'] = 'EMPATE';
$tieUnits[1]['source_data']['vehicle_load_report']['fdTrack'] = 'EMPATE';
$tieUnits[0]['source_rows']['vehicle_load_report'] = 2;
$tieUnits[1]['source_rows']['vehicle_load_report'] = 3;
$stable = (new ConsistDocumentBuilder())->build(
    str_repeat('c', 64),
    ['units' => $tieUnits],
    $catalog,
    1,
    $period,
)->toArray();
assert($stable['units'][0]['vin'] === $tieUnits[0]['vin']);
$spreadsheet->disconnectWorksheets();
echo "[PASS] Consist document builder golden master 624/624\n";
