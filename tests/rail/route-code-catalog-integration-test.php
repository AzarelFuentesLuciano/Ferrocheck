<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Repositories\Rail\RouteCodeCatalogRepository;
use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistOperationalPeriod;
use App\Services\Rail\Consist\RouteCodeCatalogLoader;
use App\Services\Rail\Consist\RouteCodeCatalogService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$pdo = new PDO('sqlite::memory:', options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec(
    'CREATE TABLE rail_catalog_versions(
        id INTEGER PRIMARY KEY AUTOINCREMENT,source_filename TEXT NOT NULL,source_sha256 TEXT NOT NULL UNIQUE,
        imported_by INTEGER,imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,active INTEGER NOT NULL,
        origin TEXT NOT NULL DEFAULT "manual",seed_migration TEXT
    );
    CREATE TABLE rail_catalog_route_codes(
        id INTEGER PRIMARY KEY AUTOINCREMENT,catalog_version_id INTEGER NOT NULL,source_row INTEGER NOT NULL,
        route_code TEXT NOT NULL,route_king TEXT,market TEXT,shipping_destination TEXT,carrier TEXT,
        heavy_duty TEXT,usa_canada TEXT,production_plant TEXT,rc_plant TEXT,load_by TEXT,border_crossing TEXT,
        seed_migration TEXT,UNIQUE(catalog_version_id,source_row)
    );
    CREATE TABLE auditoria_eventos(
        id INTEGER PRIMARY KEY AUTOINCREMENT,usuario_id INTEGER,accion TEXT,modulo TEXT,entidad TEXT,
        entidad_id TEXT,resultado TEXT,valor_nuevo_json TEXT,created_at TEXT
    )'
);
$repository = new RouteCodeCatalogRepository($pdo);
$loader = new RouteCodeCatalogLoader($repository);
$service = new RouteCodeCatalogService($repository);
$allowed = new AuthenticatedUser(7, 'Admin', 'admin', ['Administrador'], ['rail.catalogos.importar']);
$denied = new AuthenticatedUser(8, 'Consulta', 'consulta', [], ['rail.ver']);

$passed = 0;
$failed = 0;
$test = static function (string $name, callable $assertion) use (&$passed, &$failed): void {
    try {
        $ok = $assertion() === true;
    } catch (Throwable $exception) {
        $ok = false;
        $name .= ' (' . $exception::class . ': ' . $exception->getMessage() . ')';
    }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $name);
};
$throws = static function (callable $callback, string $message): bool {
    try {
        $callback();
    } catch (Throwable $exception) {
        return str_contains($exception->getMessage(), $message);
    }
    return false;
};

$test('catálogo inexistente bloquea generación con instrucción clara', static fn (): bool =>
    $throws(static fn () => $loader->load(), 'migración oficial')
);

$directory = sys_get_temp_dir() . '/route-catalog-test-' . bin2hex(random_bytes(6));
mkdir($directory, 0770, true);
$path = $directory . '/catalogo-sintetico.xlsx';
$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('route_codes');
$sheet->fromArray([
    ['Route Code', 'Route King', 'Market', 'Shipping Destination', 'Carrier'],
    ['RUTA-01', 'KING-01', 'MERCADO-01', 'DESTINO-01', 'CARRIER-01'],
    ['RUTA-02', 'KING-02', 'MERCADO-02', 'DESTINO-02', 'CARRIER-02'],
]);
IOFactory::createWriter($book, 'Xlsx')->save($path);
$book->disconnectWorksheets();
$upload = [
    'error' => UPLOAD_ERR_OK,
    'tmp_name' => $path,
    'size' => filesize($path),
    'name' => 'catalogo-sintetico.xlsx',
];

$imported = $service->import($upload, $allowed);
$test('catálogo importado queda activo y versionado por SHA-256', static fn (): bool =>
    ($imported['version']['active'] ?? false) === true
    && ($imported['version']['record_count'] ?? 0) === 2
    && ($imported['version']['source_sha256'] ?? '') === hash_file('sha256', $path)
);
$test('loader consume rutas exclusivamente desde versión activa', static fn (): bool =>
    array_keys($loader->load()['routes']) === ['RUTA-01', 'RUTA-02']
);
$analysis = ['units' => [[
    'vin' => 'VIN-SINTETICO-01',
    'presence' => ['vehicle_load_report' => true, 'shippers' => true, 'cnacs' => true],
    'duplicate_counts' => ['vehicle_load_report' => 0, 'shippers' => 0, 'cnacs' => 0],
    'source_rows' => ['vehicle_load_report' => 2],
    'source_data' => [
        'vehicle_load_report' => [
            'fdmanufacturerroutecode' => 'RUTA-01',
            'fdTransportationName1' => 'PLATAFORMA-01',
            'fdSCAC' => 'CARRIER-01',
            'fdTrack' => 'TRACK-01',
        ],
        'shippers' => ['Textbox7' => 'SHIPPER-01'],
        'cnacs' => ['Pedimento' => 'PEDIMENTO-01'],
    ],
]]];
$draft = (new ConsistDocumentBuilder())->build(
    str_repeat('a', 64),
    $analysis,
    $loader->load(),
    7,
    ConsistOperationalPeriod::fromInput('2026-07-29', '2026-07-30'),
);
$test('generación usa el catálogo activo y conserva periodo válido', static fn (): bool =>
    $draft->toArray()['total_units'] === 1
    && $draft->toArray()['fecha_inicio'] === '2026-07-29'
    && $draft->toArray()['units'][0]['market'] === 'MERCADO-01'
);
$test('importación registra auditoría sin guardar el archivo', static fn (): bool =>
    (int) $pdo->query("SELECT COUNT(*) FROM auditoria_eventos WHERE accion='rail.catalogos.importar'")->fetchColumn() === 1
    && !str_contains((string) $pdo->query('SELECT valor_nuevo_json FROM auditoria_eventos')->fetchColumn(), 'MERCADO-01')
);
$beforeDenied = (int) $pdo->query('SELECT COUNT(*) FROM rail_catalog_versions')->fetchColumn();
$test('usuario sin permiso no importa catálogo', static fn (): bool =>
    $throws(static fn () => $service->import($upload, $denied), 'permiso')
    && (int) $pdo->query('SELECT COUNT(*) FROM rail_catalog_versions')->fetchColumn() === $beforeDenied
);

$pdo->exec('UPDATE rail_catalog_versions SET active=0');
$test('catálogo inactivo bloquea generación', static fn (): bool =>
    $throws(static fn () => $loader->load(), 'migración oficial')
);
$pdo->exec('UPDATE rail_catalog_versions SET active=1');
$activeBeforeFailure = (int) $pdo->query('SELECT id FROM rail_catalog_versions WHERE active=1')->fetchColumn();
$routesBeforeFailure = (int) $pdo->query('SELECT COUNT(*) FROM rail_catalog_route_codes')->fetchColumn();
$invalidCatalog = $loader->load();
$invalidCatalog['version']['source_sha256'] = str_repeat('b', 64);
$invalidCatalog['routes']['DUPLICATE-1'] = [
    'source_row' => 10, 'route_code' => 'DUPLICATE', 'route_king' => '',
    'market' => '', 'shipping_destination' => '', 'carrier' => '',
];
$invalidCatalog['routes']['DUPLICATE-2'] = [
    'source_row' => 10, 'route_code' => 'DUPLICATE', 'route_king' => '',
    'market' => '', 'shipping_destination' => '', 'carrier' => '',
];
$test('fallo de persistencia revierte versión, rutas y auditoría', static function () use (
    $repository,
    $invalidCatalog,
    $pdo,
    $activeBeforeFailure,
    $routesBeforeFailure,
): bool {
    try {
        $repository->import($invalidCatalog, 7);
    } catch (Throwable) {
        return (int) $pdo->query('SELECT id FROM rail_catalog_versions WHERE active=1')->fetchColumn()
                === $activeBeforeFailure
            && (int) $pdo->query('SELECT COUNT(*) FROM rail_catalog_route_codes')->fetchColumn()
                === $routesBeforeFailure
            && (int) $pdo->query("SELECT COUNT(*) FROM auditoria_eventos WHERE accion='rail.catalogos.importar'")
                ->fetchColumn() === 1;
    }
    return false;
});

unlink($path);
rmdir($directory);
echo "\nResumen Route Catalog Integration: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
