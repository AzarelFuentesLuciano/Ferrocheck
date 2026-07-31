<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistDraftGenerator;
use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;

$source = static function (string $label, array $vins, array $duplicates = []): array {
    $records = [];
    foreach ($vins as $vin) {
        $records[$vin] = ['data' => ['VIN' => $vin, 'Fuente' => $label]];
    }
    return [
        'label'=>$label,
        'valid_records'=>count($vins) + array_sum($duplicates),
        'unique_count'=>count($vins),
        'duplicate_count'=>array_sum($duplicates),
        'empty_vin'=>0,
        'vins'=>array_fill_keys($vins, true),
        'duplicates'=>$duplicates,
        'records'=>$records,
    ];
};
$vins = array_map(static fn (int $number): string => sprintf('VIN%04d', $number), range(1, 624));
$duplicateVins = array_slice($vins, 0, 24);
$duplicates = array_fill_keys($duplicateVins, 1);
$analysis = (new ConsistVinCrossAnalyzer())->analyze(
    $source('Vehicle Load', $vins),
    $source('Shippers', $vins),
    $source('CNACS', $vins, $duplicates),
);
$generator = new ConsistDraftGenerator();
$token = str_repeat('a', 64);
$draft = $generator->generate($token, $analysis, [], 10, [
    'fecha_consist'=>'2026-07-30',
    'descripcion'=>'Lote de prueba',
    'observaciones'=>'Borrador técnico',
]);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('análisis conserva 624 VIN presentes en los tres archivos',
    $analysis['consistency']['total_unique_combined'] === 624
    && $analysis['consistency']['total_present_in_all'] === 624
    && count($analysis['units']) === 624
);
$test('conserva 24 duplicados internos', $analysis['consistency']['total_internal_duplicates'] === 24);
$test('duplicados CNACS no crean filas ni bloquean unidades',
    $analysis['consistency']['total_consist_candidates'] === 624
    && $draft['total_units'] === 624
);
$test('borrador conserva token, usuario y datos crudos por fuente',
    $draft['analysis_token'] === $token
    && $draft['created_by'] === 10
    && ($draft['units'][0]['source_data']['vehicle_load_report']['VIN'] ?? '') !== ''
);
$test('rechaza VIN que no pertenece al análisis', (static function () use ($generator, $token, $analysis): bool {
    try { $generator->generate($token, $analysis, ['VIN-AJENO'], 10, ['fecha_consist'=>'2026-07-30']); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'no pertenece'); }
    return false;
})());
$test('primera coincidencia CNACS permanece elegible',
    in_array($duplicateVins[0], array_column($draft['units'], 'vin'), true)
);

$missingAnalysis = (new ConsistVinCrossAnalyzer())->analyze(
    $source('Vehicle Load', ['ALL','NO-SHIPPER','NO-CNACS']),
    $source('Shippers', ['ALL','NO-VEHICLE','NO-CNACS']),
    $source('CNACS', ['ALL','NO-VEHICLE','NO-SHIPPER']),
);
$test('clasifica VIN faltantes en cada fuente',
    $missingAnalysis['consistency']['missing_vehicle_load_report'] === 1
    && $missingAnalysis['consistency']['missing_shippers'] === 1
    && $missingAnalysis['consistency']['missing_cnacs'] === 1
);

echo "\nResumen Consist Draft Generator: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
