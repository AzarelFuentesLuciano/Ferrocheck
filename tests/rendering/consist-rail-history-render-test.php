<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\ViewModels\Rail\ConsistUploadViewModel;

$root = dirname(__DIR__, 2);
$configuration = require $root . '/config/consist-rail-import.php';
$railBaseUrl = '/Ferrocheck/public';
$railEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$consistUpload = new ConsistUploadViewModel('csrf', [], null, $configuration);
$render = static function (string $subsection, array $page) use ($root, $railBaseUrl, $railEscape, $consistUpload): string {
    $railSubsection = $subsection;
    $railConsistPage = $page;
    ob_start();
    require $root . '/app/Views/rail/consist-rail.php';
    return (string) ob_get_clean();
};
$history = $render('historial', [
    'items'=>[[
        'id'=>1,'folio'=>'CR-2026-000001','status'=>'borrador',
        'total_units'=>624,'total_platforms'=>78,'created_by_name'=>'Admin',
        'source_filename'=>'vascor_sm_db.xlsx','created_at'=>'2026-07-30 10:00:00',
        'operational_summary'=>[
            'pending_platforms'=>3,
            'confirmed_platforms'=>75,
            'total_loaded_platforms'=>78,
            'is_consistent'=>true,
        ],
        'issues'=>[['type'=>'route_code_resolution_summary','fallback_unit_count'=>2,'route_codes'=>['75','BR']]],
    ]],
    'total'=>1,'page'=>1,'pages'=>1,
    'filters'=>['folio'=>'CR-2026','vin'=>'VIN1','estado'=>'borrador','desde'=>'2026-07-01','hasta'=>'2026-07-31','usuario'=>'Admin'],
]);
$confirmed = $render('consultar', [
    'id'=>1,'folio'=>'CR-2026-000001','status'=>'borrador','created_at'=>'2026-07-30',
    'created_by_name'=>'Admin','total_units'=>1,'total_platforms'=>1,'issues'=>[],
    'filters'=>['vin_buscar'=>'','plataforma'=>'','incidencia'=>''],'pagination'=>['page'=>1,'pages'=>1,'total'=>1],
    'units'=>[[
        'global_position'=>1,'platform_position'=>1,'vin'=>'VIN1','track'=>'T1','route_code'=>'20IL',
        'market'=>'NAFTA','shipping_destination'=>'DEST','final_data_json'=>['fdTransportationName1'=>'P1'],
        'platform_number'=>'P1',
        'issues_json'=>[],
    ]],
    'all_platforms'=>['P1'],
]);
$warningDetail = $render('consultar', [
    'unit'=>[
        'vin'=>'VIN-WARNING','global_position'=>1,'platform_position'=>1,'route_code'=>'75',
        'market'=>'NAFTA','shipping_destination'=>'Amarillo, TX',
        'final_data_json'=>['fdTransportationName1'=>'P1'],
        'platform_number'=>'P1',
        'vehicle_load_data_json'=>[],'shippers_data_json'=>[],'cnacs_selected_data_json'=>[],
        'cnacs_additional_data_json'=>[],'trace_json'=>['route_code_resolution'=>['mode'=>'historical_first_match','selected_source_row'=>288,'variant_count'=>2]],
        'issues_json'=>[['type'=>'route_code_historical_fallback','message'=>'Primera coincidencia histórica aplicada.']],
    ],
    'consist'=>['id'=>1],
]);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('historial muestra métricas operativas y acción Ver', str_contains($history, 'CR-2026-000001')
    && str_contains($history, '>Ver</a>')
    && str_contains($history, 'Pendientes')
    && str_contains($history, 'Confirmadas')
    && str_contains($history, 'Total plataformas'));
$test('historial conserva filtros por folio, VIN, estado y fecha', str_contains($history, 'name="folio"')
    && str_contains($history, 'name="vin"')
    && str_contains($history, 'name="estado"')
    && str_contains($history, 'name="desde"')
    && str_contains($history, 'name="hasta"'));
$test('historial usa tabla responsive y paginación', str_contains($history, 'rail-consist-table-wrap')
    && str_contains($history, 'Página 1 de 1'));
$test('historial muestra resumen persistido de fallback histórico', str_contains($history, '2 unidad(es)')
    && str_contains($history, '75, BR'));
$test('detalle muestra warning y trazabilidad de resolución', str_contains($warningDetail, 'Primera coincidencia histórica aplicada.')
    && str_contains($warningDetail, 'historical_first_match')
    && str_contains($warningDetail, '288'));
$test('borrador no muestra acciones fuera de alcance', str_contains($confirmed, 'borrador')
    && !str_contains($confirmed, 'Quitar')
    && !str_contains($confirmed, 'Confirmar Consist')
    && !str_contains($confirmed, 'Cancelar borrador'));
$test('borrador y filtro muestran la plataforma normalizada',
    substr_count($confirmed, 'P1') >= 2
    && str_contains($confirmed, '<option value="P1"'));
$test('detalle muestra la plataforma normalizada', str_contains($warningDetail, '<dd>P1</dd>'));

echo "\nResumen Consist Rail History Render: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
