<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = (string) file_get_contents($root . '/database/migrations/20260730_016_create_rail_consists.sql');
$catalogMigration = (string) file_get_contents($root . '/database/migrations/20260731_017_create_rail_route_catalog.sql');
$summaryMigration = (string) file_get_contents($root . '/database/migrations/20260731_018_add_rail_consist_operational_summary.sql');
$officialCatalogMigration = (string) file_get_contents($root . '/database/migrations/20260731_019_seed_official_rail_route_catalog.sql');
$repository = (string) file_get_contents($root . '/app/Repositories/Rail/ConsistRepository.php');
$workflow = (string) file_get_contents($root . '/app/Services/Rail/Consist/ConsistWorkflowService.php');
$builder = (string) file_get_contents($root . '/app/Services/Rail/Consist/ConsistDocumentBuilder.php');
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
$test('catálogo de rutas queda versionado, indexado y protegido',
    str_contains($catalogMigration, 'rail_catalog_route_codes')
    && str_contains($catalogMigration, 'uq_rail_catalog_route_version_code')
    && str_contains($catalogMigration, 'fk_rail_catalog_route_version')
    && str_contains($catalogMigration, 'rail.catalogos.importar'));
$test('catálogo oficial queda autocontenido, preserva filas fuente y no exige usuario ficticio',
    str_contains($officialCatalogMigration, '42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e')
    && str_contains($officialCatalogMigration, 'uq_rail_catalog_route_version_source')
    && str_contains($officialCatalogMigration, "NULL,0,'system'")
    && !str_contains($officialCatalogMigration, 'docs/rail/consist/referencias'));
$test('POST conserva CSRF y no expone confirmación/cancelación',
    str_contains($controller, '$this->csrf->validate')
    && str_contains($controller, 'handleCatalogImport')
    && !str_contains($controller, '$_POST')
    && !str_contains($controller, "'confirm_consist'")
    && !str_contains($controller, "'cancel_consist'"));

$test('resumen operativo persiste y se hidrata para detalle e historial',
    str_contains($summaryMigration, 'operational_summary_json')
    && str_contains($repository, "'operational_summary'")
    && str_contains($repository, "'operational_summary_json'"));
$test('advertencias de resolución reutilizan issues, trace y auditoría general',
    str_contains($repository, 'route_code_resolution_summary')
    && str_contains($repository, 'route_code_resolution')
    && str_contains($repository, "'trace' => \$this->json(\$unit['trace'])")
    && str_contains($repository, "'issues' => \$this->json(\$unit['issues'])"));

$test('contradicciones de Summary quedan en auditoría y no en el XLSX',
    str_contains($workflow, "(array) (\$result['summary_warnings'] ?? [])")
    && str_contains($repository, "'summary_warnings' => \$summaryWarnings"));
$test('plataforma real se relaciona sin bloques posicionales de ocho',
    str_contains($builder, "'platform_group_position'")
    && str_contains($repository, "\$unit['platform_group_position']")
    && !str_contains($repository, "intdiv((int) \$unit['global_position'] - 1, 8)"));
$test('hidratación normaliza plataforma asociativa, posicional e histórica',
    str_contains($repository, 'ConsistUnitDataNormalizer')
    && str_contains($repository, "\$unit['platform_number']"));

echo "\nResumen Consist Persistence Contract: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
