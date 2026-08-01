<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/tools/rail/generate-route-catalog-seed.php';

use App\Services\Rail\Consist\RouteCodeResolver;

$root = dirname(__DIR__, 2);
$source = $root . '/docs/rail/consist/referencias/vascor_sm_db.xlsx';
$migrationPath = $root . '/database/migrations/20260731_019_seed_official_rail_route_catalog.sql';
$rollbackPath = $root . '/database/migrations/20260731_019_seed_official_rail_route_catalog.rollback.sql';
$generator = new OfficialRouteCatalogSeedGenerator();
$rows = $generator->read($source);
$generated = $generator->migration($rows);
$migration = (string) file_get_contents($migrationPath);
$rollback = (string) file_get_contents($rollbackPath);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $condition) use (&$passed, &$failed): void {
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
};
$throws = static function (callable $callback, string $message): bool {
    try {
        $callback();
    } catch (Throwable $exception) {
        return str_contains($exception->getMessage(), $message);
    }
    return false;
};

$test('fuente oficial conserva SHA-256 aprobado',
    hash_file('sha256', $source) === OfficialRouteCatalogSeedGenerator::SOURCE_SHA256);
$test('route_codes produce exactamente 436 filas', count($rows) === 436);
$test('encabezados oficiales permanecen completos',
    array_keys(array_diff_key($rows[0], ['source_row' => true])) === OfficialRouteCatalogSeedGenerator::HEADERS);
$test('migración versionada coincide byte por byte con el generador', $migration === $generated);
$test('migración contiene 436 filas autocontenidas',
    preg_match_all('/^\\(@official_catalog_version_id,/m', $migration) === 436);
$test('migración no requiere XLSX ni PhpSpreadsheet en ejecución',
    !str_contains($migration, 'docs/rail/consist/referencias')
    && !str_contains($migration, 'PhpSpreadsheet')
    && str_contains($migration, "VALUES ('vascor_sm_db.xlsx',@official_sha256"));
$test('migración es idempotente y deja una versión activa',
    str_contains($migration, 'IF NOT EXISTS')
    && str_contains($migration, 'ON DUPLICATE KEY UPDATE')
    && str_contains($migration, 'UPDATE rail_catalog_versions SET active=0')
    && str_contains($migration, 'UPDATE rail_catalog_versions SET active=1'));
$test('imported_by nulo queda restringido a versiones de sistema',
    str_contains($migration, 'chk_rail_catalog_system_importer')
    && str_contains($migration, "imported_by IS NOT NULL OR origin='system'")
    && str_contains($rollback, 'DROP CONSTRAINT IF EXISTS chk_rail_catalog_system_importer'));
$test('modelo preserva las once columnas y la fila fuente',
    str_contains($migration, 'production_plant')
    && str_contains($migration, 'rc_plant')
    && str_contains($migration, 'border_crossing')
    && str_contains($migration, 'uq_rail_catalog_route_version_source'));
$test('rollback limita eliminación al marcador de la 019',
    str_contains($rollback, "seed_migration='20260731_019_seed_official_rail_route_catalog'")
    && str_contains($rollback, ':created')
    && str_contains($rollback, ':adopted')
    && !str_contains($rollback, 'DROP TABLE'));

$resolver = new RouteCodeResolver();
$equivalent = [
    'route_variants' => ['R1' => [
        ['source_row' => 2, 'route_code' => 'R1', 'market' => 'NAFTA', 'shipping_destination' => 'DEST', 'carrier' => 'A'],
        ['source_row' => 3, 'route_code' => 'R1', 'market' => 'nafta', 'shipping_destination' => ' DEST ', 'carrier' => 'B'],
    ]],
];
$resolved = $resolver->resolve($equivalent, 'r1', 'X');
$test('duplicados con salida equivalente se resuelven sin pérdida',
    trim((string) $resolved['shipping_destination']) === 'DEST');
$byCarrier = [
    'route_variants' => ['R2' => [
        ['route_code' => 'R2', 'market' => 'NAFTA', 'shipping_destination' => 'DEST-A', 'carrier' => 'A'],
        ['route_code' => 'R2', 'market' => 'NAFTA', 'shipping_destination' => 'DEST-B', 'carrier' => 'B'],
    ]],
];
$test('destino operativo resuelve una variante inequívoca sin comparar Carrier',
    $resolver->resolve($byCarrier, 'R2', 'XFXE', 'Entrega en Dest-B')['shipping_destination'] === 'DEST-B');
$test('ambigüedad real conserva primera fila histórica',
    ($resolver->resolve($byCarrier, 'R2', 'XFXE', 'SIN COINCIDENCIA')['resolution_mode'] ?? '')
        === 'historical_first_match');
$test('Route Code inexistente se rechaza explícitamente',
    $throws(static fn () => $resolver->resolve($byCarrier, 'NO-EXISTE', 'X'), 'no existe'));

echo "\nResumen Official Route Catalog Seed: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
