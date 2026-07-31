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
        'issues_json'=>[],
    ]],
]);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('historial muestra columnas y acción Continuar', str_contains($history, 'CR-2026-000001')
    && str_contains($history, '>Ver</a>')
    && str_contains($history, 'Plataformas'));
$test('historial conserva filtros por folio, VIN, estado y fecha', str_contains($history, 'name="folio"')
    && str_contains($history, 'name="vin"')
    && str_contains($history, 'name="estado"')
    && str_contains($history, 'name="desde"')
    && str_contains($history, 'name="hasta"'));
$test('historial usa tabla responsive y paginación', str_contains($history, 'rail-consist-table-wrap')
    && str_contains($history, 'Página 1 de 1'));
$test('borrador no muestra acciones fuera de alcance', str_contains($confirmed, 'borrador')
    && !str_contains($confirmed, 'Quitar')
    && !str_contains($confirmed, 'Confirmar Consist')
    && !str_contains($confirmed, 'Cancelar borrador'));

echo "\nResumen Consist Rail History Render: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
