<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;

$source = static fn (string $label, array $vins, array $duplicates = [], int $empty = 0): array => [
    'label'=>$label,
    'valid_records'=>count($vins) + array_sum($duplicates),
    'unique_count'=>count($vins),
    'duplicate_count'=>array_sum($duplicates),
    'empty_vin'=>$empty,
    'vins'=>array_fill_keys($vins, true),
    'duplicates'=>$duplicates,
];
$analyzer = new ConsistVinCrossAnalyzer(2);
$result = $analyzer->analyze(
    $source('VLR', ['ALL','V','VS','VC','EXTRA1','EXTRA2','EXTRA3'], ['V'=>1], 1),
    $source('Shippers', ['ALL','S','VS','SC'], ['S'=>2], 2),
    $source('CNACS', ['ALL','C','VC','SC'], ['C'=>1], 3),
);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$expected = [
    'present_in_all'=>1, 'only_vehicle_load_report'=>4, 'only_shippers'=>1, 'only_cnacs'=>1,
    'vehicle_load_report_and_shippers'=>1, 'vehicle_load_report_and_cnacs'=>1, 'shippers_and_cnacs'=>1,
];
foreach ($expected as $key => $count) {
    $test("clasifica {$key}", ($result['categories'][$key]['count'] ?? -1) === $count);
}
$test('VIN repetido participa una sola vez en el cruce', $result['consistency']['total_unique_combined'] === 10);
$test('calcula ausencias y coincidencia triple', $result['consistency']['total_present_in_all'] === 1 && $result['consistency']['total_with_any_absence'] === 9);
$test('conserva duplicados internos separados', $result['categories']['duplicates_vehicle_load_report']['count'] === 1 && $result['consistency']['total_internal_duplicates'] === 4);
$test('suma VIN vacíos', $result['consistency']['total_empty_vins'] === 6);
$test('limita muestras e informa restantes', count($result['categories']['only_vehicle_load_report']['sample']) === 2 && $result['categories']['only_vehicle_load_report']['remaining'] === 2);
$empty = $analyzer->analyze($source('V', []), $source('S', []), $source('C', []));
$test('acepta conjuntos vacíos', $empty['consistency']['total_unique_combined'] === 0);
$limited = (new ConsistVinCrossAnalyzer(500))->analyze(
    $source('V', array_map(static fn (int $i): string => 'VIN' . $i, range(1, 60))),
    $source('S', []),
    $source('C', []),
);
$test('impone máximo absoluto de 50 muestras', count($limited['categories']['only_vehicle_load_report']['sample']) === 50);

echo "\nResumen Consist VIN Cross Analyzer: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
