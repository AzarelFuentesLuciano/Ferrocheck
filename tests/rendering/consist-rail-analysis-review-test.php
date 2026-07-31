<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\ViewModels\Rail\ConsistAnalysisViewModel;
use App\ViewModels\Rail\ConsistUploadViewModel;

$root = dirname(__DIR__, 2);
$configuration = require $root . '/config/consist-rail-import.php';
$units = [];
for ($index = 1; $index <= 60; $index++) {
    $vin = sprintf('VIN%03d', $index);
    $units[] = [
        'vin'=>$vin,
        'presence'=>['vehicle_load_report'=>true,'shippers'=>true,'cnacs'=>true],
        'status'=>'completo',
        'duplicate'=>false,
        'eligible'=>true,
        'observations'=>'',
        'source_data'=>[
            'vehicle_load_report'=>['fdWholeVIN'=>$vin,'fdLoadId'=>'LOAD-1'],
            'shippers'=>['fdWholeVIN1'=>$vin,'fdBillNumber'=>'BILL-1'],
            'cnacs'=>['VIN'=>$vin,'InvoiceNo'=>'INV-1'],
        ],
    ];
}
$result = [
    'files'=>[
        'vehicle_load_report'=>['unique_vins'=>60],
        'shippers'=>['unique_vins'=>60],
        'cnacs'=>['unique_vins'=>60],
    ],
    'categories'=>[],
    'consistency'=>[
        'total_unique_combined'=>60,'total_present_in_all'=>60,
        'missing_vehicle_load_report'=>0,'missing_shippers'=>0,'missing_cnacs'=>0,
        'total_internal_duplicates'=>0,'total_inconsistencies'=>0,'total_consist_candidates'=>60,
    ],
    'units'=>$units,
];
$analysis = new ConsistAnalysisViewModel($result, ['vin_detalle'=>'VIN001']);
$upload = new ConsistUploadViewModel('csrf', [], ['valid'=>true,'files'=>[]], $configuration, str_repeat('a', 64), true, $analysis);
$railSubsection = 'registrar';
$railBaseUrl = '/Ferrocheck/public';
$railEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$consistUpload = $upload;
ob_start();
require $root . '/app/Views/rail/consist-rail.php';
$html = (string) ob_get_clean();
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('resumen ejecutivo renderiza indicadores requeridos', str_contains($html, 'Resumen ejecutivo')
    && str_contains($html, 'Faltantes en Vehicle Load')
    && str_contains($html, 'Candidatos al Consist'));
$test('tabla detallada es paginada y responsive', str_contains($html, 'rail-consist-table-wrap')
    && substr_count($html, 'Ver detalle') === 25
    && $analysis->pagination['pages'] === 3);
$test('incluye búsqueda, filtros y selección elegible', str_contains($html, 'name="vin_buscar"')
    && str_contains($html, 'name="estado_cruce"')
    && str_contains($html, 'name="duplicados"')
    && str_contains($html, 'Generar Consist')
    && !str_contains($html, 'name="selected_vins[]"'));
$test('detalle muestra columnas reales separadas por fuente', str_contains($html, 'Detalle de VIN001')
    && str_contains($html, 'fdLoadId')
    && str_contains($html, 'fdBillNumber')
    && str_contains($html, 'InvoiceNo'));
$test('servidor reconstruye borrador sin aceptar datos operativos', str_contains($html, 'name="batch_token"')
    && str_contains($html, 'name="action" value="create_consist_draft"')
    && !str_contains($html, 'name="fecha_consist"')
    && !str_contains($html, 'name="selected_vins[]"'));

echo "\nResumen Consist Analysis Review: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
