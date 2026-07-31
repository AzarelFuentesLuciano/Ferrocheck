<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistOperationalSummary;

$service = new ConsistOperationalSummary();
$source = static fn (string $platform): array => [
    'source_data' => ['vehicle_load_report' => ['fdTransportationName1' => $platform]],
];
$final = static fn (string $platform, mixed $pedimento): array => [
    'final_columns' => ['fdTransportationName1' => $platform, 'CNACS-Pedimento' => $pedimento],
];
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $condition) use (&$passed, &$failed): void {
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
};

$summary = $service->fromFinalUnits(
    [$source('  p-01 '), $source('P-01'), $source(''), $source('P-02'), $source('P-03')],
    [
        $final('P-01', ' Pending '),
        $final(' p-01 ', 'PENDING'),
        $final('P-02', '16806003188'),
        $final('P-03', ''),
    ],
);
$test('Vehicle Load deduplica plataformas y excluye vacías',
    $summary['total_loaded_platforms'] === 3
    && $summary['loaded_platforms'] === ['P-01', 'P-02', 'P-03']);
$test('varios VIN pendientes cuentan una plataforma',
    $summary['pending_platforms'] === 2
    && $summary['pending_platform_numbers'] === ['P-01', 'P-03']);
$test('pedimento válido confirma la plataforma', $summary['confirmed_platforms'] === 1);
$test('vacío final se considera pendiente', $summary['empty_pedimento_is_pending'] === true);
$test('ecuación operativa se conserva',
    $summary['pending_platforms'] + $summary['confirmed_platforms'] === $summary['total_loaded_platforms']);

$none = $service->fromFinalUnits([$source('P-01'), $source('P-02')], [
    $final('P-01', '123'), $final('P-02', '456'),
]);
$test('cero pendientes confirma todas', $none['pending_platforms'] === 0 && $none['confirmed_platforms'] === 2);

$all = $service->fromFinalUnits([$source('P-01'), $source('P-02')], [
    $final('P-01', 'pending'),
    $final('P-02', ' Falta   agregar este VIN en hoja de CNACS '),
]);
$test('pendientes igual al total produce cero confirmadas',
    $all['pending_platforms'] === 2 && $all['confirmed_platforms'] === 0);

$invalid = $service->fromFinalUnits([$source('P-01')], [
    $final('P-01', 'Pending'), $final('P-99', 'Pending'),
]);
$test('exceso genera inconsistencia visible y nunca negativo',
    $invalid['is_consistent'] === false
    && $invalid['confirmed_platforms'] === 0
    && ($invalid['inconsistencies'][0]['type'] ?? '') === 'pending_platforms_exceed_total');
$test('solo reconoce marcadores verificados',
    $service->pendingValues() === ['PENDING', 'FALTA AGREGAR ESTE VIN EN HOJA DE CNACS']);

echo "\nResumen Operational Summary: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
