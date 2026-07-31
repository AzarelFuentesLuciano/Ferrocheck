<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Repositories\Rail\ConsistRepository;
use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistGoldenMasterComparator;
use App\Services\Rail\Consist\ConsistWorkflowService;
use App\Services\Rail\Consist\ConsistWorkbookExporter;
use App\Services\Rail\Consist\ConsistWorkbookValidator;
use App\Services\Rail\Consist\RouteCodeCatalogLoader;
use PhpOffice\PhpSpreadsheet\IOFactory;

if (getenv('CONSIST_LOCAL_RUN') !== '1') {
    fwrite(STDERR, "SKIP: CONSIST_LOCAL_RUN=1 es obligatorio.\n");
    exit(2);
}

$dsn = getenv('CONSIST_LOCAL_DSN') ?: '';
if (!preg_match('/^mysql:host=(127\.0\.0\.1|localhost);port=\d+;dbname=ferrocheck;/', $dsn)) {
    throw new RuntimeException('La prueba sólo permite la base local ferrocheck.');
}
$pdo = new PDO($dsn, getenv('CONSIST_LOCAL_DB_USER') ?: '', getenv('CONSIST_LOCAL_DB_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$root = dirname(__DIR__, 2);
$template = $root . '/docs/rail/consist/referencias/Plantilla_Consist.xlsm';
$catalogPath = $root . '/docs/rail/consist/referencias/vascor_sm_db.xlsx';
$formatPath = $root . '/docs/rail/consist/referencias/Consist Rail del 29 al 30 de Julio de 2026.xlsx';
$hashes = [
    $template => hash_file('sha256', $template),
    $catalogPath => hash_file('sha256', $catalogPath),
    $formatPath => hash_file('sha256', $formatPath),
];

$reader = IOFactory::createReaderForFile($template);
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['  Vehicle Load Report', 'Shippers', 'CNACS']);
$spreadsheet = $reader->load($template);
$records = static function (string $sheetName, string $vinHeader) use ($spreadsheet): array {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $headers = [];
    foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
        $headers[$cell->getColumn()] = trim((string) $cell->getFormattedValue());
    }
    $vinColumn = array_search($vinHeader, $headers, true);
    $result = [];
    for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
        $vin = mb_strtoupper(trim((string) $sheet->getCell($vinColumn . $row)->getFormattedValue()), 'UTF-8');
        if ($vin === '') {
            continue;
        }
        $data = [];
        foreach ($headers as $column => $header) {
            $value = trim((string) $sheet->getCell($column . $row)->getFormattedValue());
            if ($header !== '' && $value !== '') {
                $data[$header] = $value;
            }
        }
        $result[$vin][] = ['row' => $row, 'data' => $data];
    }
    return $result;
};
$vehicle = $records('  Vehicle Load Report', 'fdwholevin');
$shippers = $records('Shippers', 'fdWholeVIN1');
$cnacs = $records('CNACS', 'Vin');
$analysis = ['units' => []];
foreach ($vehicle as $vin => $vehicleRows) {
    $analysis['units'][] = [
        'vin' => $vin,
        'presence' => ['vehicle_load_report' => true, 'shippers' => isset($shippers[$vin]), 'cnacs' => isset($cnacs[$vin])],
        'duplicate_counts' => [
            'vehicle_load_report' => count($vehicleRows) - 1,
            'shippers' => count($shippers[$vin] ?? []) - 1,
            'cnacs' => count($cnacs[$vin] ?? []) - 1,
        ],
        'source_rows' => [
            'vehicle_load_report' => $vehicleRows[0]['row'],
            'shippers' => $shippers[$vin][0]['row'],
            'cnacs' => $cnacs[$vin][0]['row'],
        ],
        'source_data' => [
            'vehicle_load_report' => $vehicleRows[0]['data'],
            'shippers' => $shippers[$vin][0]['data'],
            'cnacs' => $cnacs[$vin][0]['data'],
            'cnacs_additional' => array_slice($cnacs[$vin], 1),
        ],
    ];
}
$spreadsheet->disconnectWorksheets();
$orderPayload = json_decode((string) file_get_contents($root . '/docs/rail/consist/golden-master/vin-order.json'), true);
$order = [];
foreach ($orderPayload['rows'] as $row) {
    $order[$row['vin']] = $row['consist'];
}
$actorId = (int) $pdo->query(
    "SELECT u.id FROM usuarios u JOIN usuario_roles ur ON ur.usuario_id=u.id
     JOIN roles r ON r.id=ur.rol_id WHERE u.activo=1 AND r.nombre='Administrador' ORDER BY u.id LIMIT 1"
)->fetchColumn();
$user = new AuthenticatedUser($actorId, 'Validación local', 'local', ['Administrador'], [
    'rail.consist.ver', 'rail.consist.generar', 'rail.consist.exportar',
    'rail.consist.ver_detalle', 'rail.consist.ver_historial',
]);
$directory = sys_get_temp_dir() . '/vascor-consist-local-export';
$service = new ConsistWorkflowService(
    new ConsistDocumentBuilder($order),
    new RouteCodeCatalogLoader($catalogPath),
    new ConsistRepository($pdo),
    new ConsistGoldenMasterComparator($root . '/docs/rail/consist/golden-master/consist-output.json'),
    new ConsistWorkbookExporter($formatPath),
    new ConsistWorkbookValidator(),
    $directory,
);
$draft = $service->create(str_repeat('d', 64), $analysis, '2026-07-29', '2026-07-30', $user);
$comparison = $service->compare($draft, $user);
$export = $service->export((int) $draft['id'], $user);
foreach ($hashes as $path => $hash) {
    if (hash_file('sha256', $path) !== $hash) {
        throw new RuntimeException('Una referencia cambió durante la prueba.');
    }
}
echo json_encode([
    'consist_id' => (int) $draft['id'],
    'folio' => $draft['folio'],
    'total_units' => (int) $draft['total_units'],
    'total_platforms' => (int) $draft['total_platforms'],
    'golden_matches' => (int) $comparison['matches'],
    'filename' => $export['filename'],
    'path' => $export['path'],
    'sha256' => $export['sha256'],
    'summary_rows' => $export['total_summary_rows'],
    'validation' => $export['validation'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
