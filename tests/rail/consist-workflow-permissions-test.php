<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Repositories\Rail\ConsistRepository;
use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistWorkflowService;
use App\Services\Rail\Consist\ConsistWorkbookExporter;
use App\Services\Rail\Consist\ConsistWorkbookValidator;
use App\Services\Rail\Consist\RouteCodeCatalogLoader;

final class InMemoryConsistRepository extends ConsistRepository
{
    public array $drafts = [];
    public function __construct() {}
    public function createDraft(array $draft): array { $draft['id'] = 1; return $this->drafts[1] = $draft; }
    public function find(int $id): ?array { return $this->drafts[$id] ?? null; }
    public function history(array $filters, int $page = 1, int $perPage = 25): array
    {
        return ['items' => array_values($this->drafts), 'total' => count($this->drafts), 'page' => 1, 'pages' => 1, 'filters' => $filters];
    }
}

$root = dirname(__DIR__, 2);
$vehicle = [
    'fdTransportationName1' => 'PLATFORM1', 'fdwholevin' => 'VIN1',
    'fdmanufacturerroutecode' => '20IL', 'fdSCAC' => 'XFXE', 'fdTrack' => 'TRACK1',
];
$analysis = ['units' => [[
    'vin' => 'VIN1',
    'presence' => ['vehicle_load_report' => true, 'shippers' => true, 'cnacs' => true],
    'duplicate_counts' => ['vehicle_load_report' => 0, 'shippers' => 0, 'cnacs' => 0],
    'source_data' => ['vehicle_load_report' => $vehicle, 'shippers' => [], 'cnacs' => []],
]]];
$repository = new InMemoryConsistRepository();
$service = new ConsistWorkflowService(
    new ConsistDocumentBuilder(),
    new RouteCodeCatalogLoader($root . '/docs/rail/consist/referencias/vascor_sm_db.xlsx'),
    $repository,
    null,
    new ConsistWorkbookExporter($root . '/docs/rail/consist/referencias/Consist Rail del 29 al 30 de Julio de 2026.xlsx'),
    new ConsistWorkbookValidator(),
    sys_get_temp_dir() . '/vascor-consist-permission-test',
);
$allowed = new AuthenticatedUser(1, 'Admin', 'admin', ['Administrador'], [
    'rail.consist.ver', 'rail.consist.generar', 'rail.consist.exportar',
    'rail.consist.ver_detalle', 'rail.consist.ver_historial',
]);
$denied = new AuthenticatedUser(2, 'Consulta', 'consulta', [], ['rail.ver']);
$draft = $service->create(str_repeat('a', 64), $analysis, '2026-07-29', '2026-07-30', $allowed);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('usuario con permiso genera borrador', $draft['status'] === 'borrador' && $draft['total_units'] === 1);
$test('usuario sin permiso no genera borrador', (static function () use ($service, $analysis, $denied): bool {
    try { $service->create(str_repeat('a', 64), $analysis, '2026-07-29', '2026-07-30', $denied); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'permiso'); }
    return false;
})());
$test('usuario sin permiso no consulta historial', (static function () use ($service, $denied): bool {
    try { $service->history([], 1, $denied); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'permiso'); }
    return false;
})());
$test('usuario sin permiso de exportación recibe acceso denegado', (static function () use ($service, $denied): bool {
    try { $service->export(1, $denied); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'permiso'); }
    return false;
})());
$test('ID inexistente se rechaza', (static function () use ($service, $allowed): bool {
    try { $service->export(999999, $allowed); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'no existe'); }
    return false;
})());
$test('periodo inverso se rechaza', (static function () use ($service, $analysis, $allowed): bool {
    try { $service->create(str_repeat('e', 64), $analysis, '2026-07-31', '2026-07-30', $allowed); }
    catch (DomainException $exception) { return str_contains($exception->getMessage(), 'anterior'); }
    return false;
})());

echo "\nResumen Consist Workflow Permissions: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
