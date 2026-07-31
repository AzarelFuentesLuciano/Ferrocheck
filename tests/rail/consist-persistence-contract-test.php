<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = (string) file_get_contents($root . '/database/migrations/20260730_016_create_rail_consists.sql');
$repository = (string) file_get_contents($root . '/app/Repositories/Rail/ConsistRepository.php');
$controller = (string) file_get_contents($root . '/app/Controllers/Rail/RailController.php');
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('migración crea catálogo, Consist, plataformas, unidades y secuencia',
    str_contains($migration, 'rail_catalog_versions')
    && str_contains($migration, 'rail_consists')
    && str_contains($migration, 'rail_consist_platforms')
    && str_contains($migration, 'rail_consist_units')
    && str_contains($migration, 'rail_consist_sequences'));
$test('folio único y secuencia bloqueada',
    str_contains($migration, 'UNIQUE KEY uq_rail_consists_folio')
    && str_contains($repository, 'FOR UPDATE')
    && str_contains($repository, "sprintf('CR-%04d-%06d'"));
$test('persistencia transaccional con rollback',
    str_contains($repository, 'beginTransaction')
    && str_contains($repository, 'commit()')
    && str_contains($repository, 'rollBack()'));
$test('guarda A:N, periodo, fuentes, CNACS adicionales, trazabilidad e incidencias',
    str_contains($migration, 'final_data_json')
    && str_contains($migration, 'fecha_inicio')
    && str_contains($migration, 'fecha_fin')
    && str_contains($migration, 'cnacs_additional_data_json')
    && str_contains($migration, 'trace_json')
    && str_contains($migration, 'issues_json'));
$test('historial filtra y pagina',
    str_contains($repository, "'folio' => 'c.folio'")
    && str_contains($repository, "\$filters['vin']")
    && str_contains($repository, "\$filters['usuario']")
    && str_contains($repository, 'LIMIT '));
$test('permisos mínimos aprobados',
    str_contains($migration, 'rail.consist.generar')
    && str_contains($migration, 'rail.consist.exportar')
    && str_contains($migration, 'rail.consist.ver_detalle')
    && str_contains($migration, 'rail.consist.ver_historial')
    && !str_contains($migration, 'rail.consist.confirmar'));
$test('POST conserva CSRF y no expone confirmación/cancelación',
    str_contains($controller, '$this->csrf->validate')
    && !str_contains($controller, "'confirm_consist'")
    && !str_contains($controller, "'cancel_consist'"));

echo "\nResumen Consist Persistence Contract: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
