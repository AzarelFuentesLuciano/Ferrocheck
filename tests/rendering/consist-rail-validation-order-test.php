<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Auth\Csrf;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Controllers\Rail\RailController;
use App\Services\ModuleNavigationBuilder;
use App\Services\Rail\Consist\ConsistAnalysisResultStore;
use App\Services\Rail\Consist\ConsistSpreadsheetIdentityValidator;
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;
use App\Services\Rail\Consist\ConsistUploadValidator;
use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;
use App\Services\Rail\Consist\ConsistVinExtractor;
use App\Support\Rail\RailFlashStore;

final class ConsistValidationAccessRepository implements OrganizationalAccessRepositoryInterface
{
    public function findActiveModule(string $moduleKey): ?array { return $moduleKey === 'rail' ? $this->visibleActiveModules()[0] : null; }
    public function individualModuleDecision(int $userId, int $moduleId): ?string { return null; }
    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool { return true; }
    public function activeAreaIdsForUser(int $userId): array { return [1]; }
    public function activeAreaExists(int $areaId): bool { return true; }
    public function visibleActiveModules(): array { return [['id'=>2,'clave'=>'rail','nombre'=>'Rail','descripcion'=>'','ruta'=>'rail','icono'=>'R','orden'=>10]]; }
    public function userHasActiveArea(int $userId): bool { return true; }
    public function userHasActiveModuleDecision(int $userId): bool { return false; }
}

final class CountingConsistPreviewer extends ConsistSpreadsheetPreviewer
{
    public int $calls = 0;
    public function previewBatch(array $stagedFiles, ?array $validatedIdentities = null): array
    {
        $this->calls++;
        return parent::previewBatch($stagedFiles, $validatedIdentities);
    }
}

final class CountingConsistVinExtractor extends ConsistVinExtractor
{
    public int $calls = 0;
    public function extract(array $file, array $definition, ?array $validatedHeader = null): array
    {
        $this->calls++;
        return parent::extract($file, $definition, $validatedHeader);
    }
}

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$session = [];
$csrf = new Csrf($session);
$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consist_validation_' . bin2hex(random_bytes(5));
$store = new ConsistTemporaryUploadStore(
    $root,
    $session,
    10,
    'validation-session',
    1800,
    static fn (string $source, string $target): bool => copy($source, $target),
);
$previewer = new CountingConsistPreviewer($configuration);
$extractor = new CountingConsistVinExtractor($configuration);
$analysisStore = new ConsistAnalysisResultStore($store, 10, 'validation-session');
$user = new AuthenticatedUser(10, 'Usuario Rail', 'rail', ['Administrador'], ['rail.ver']);
$controller = new RailController(
    $user,
    $csrf->token(),
    new ModuleNavigationBuilder(new OrganizationalAccess($user, new ConsistValidationAccessRepository())),
    null,
    '/Ferrocheck/public',
    $csrf,
    new RailFlashStore($session),
    $store,
    new ConsistUploadValidator($configuration, static fn (string $path): bool => is_file($path)),
    $previewer,
    $extractor,
    new ConsistVinCrossAnalyzer((int) $configuration['analysis_sample_limit']),
    $analysisStore,
    new ConsistSpreadsheetIdentityValidator($configuration),
);
$route = ['seccion'=>'consist-rail','subseccion'=>'registrar'];
$paths = [];
$headers = [
    'vehicle_load_report' => 'fdWholeVIN,fdTransportationName1,fdLoadId,fdTrack,fdDestinationLocation',
    'shippers' => 'fdWholeVIN1,fdPedimentoType,fdPortCode,fdBillNumber,fdBillDate',
    'cnacs' => 'VIN,InvoiceNo,NumRemesa,BrokerId,H/SCODE',
];
$makeFiles = static function (array $fieldSources, int $rows = 1) use (&$paths, $headers): array {
    $files = [];
    foreach ($fieldSources as $field => $sourceType) {
        $path = tempnam(sys_get_temp_dir(), 'validation_file_');
        $handle = fopen($path, 'wb');
        fwrite($handle, $headers[$sourceType] . "\n");
        for ($row = 1; $row <= $rows; $row++) {
            fwrite($handle, "{$sourceType}-{$row},uno,dos,tres,cuatro\n");
        }
        fclose($handle);
        $paths[] = $path;
        $files[$field] = ['name'=>$field.'.csv','tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];
    }
    return $files;
};
$postUpload = static function (array $files) use ($controller, $route, $csrf): string {
    $controller->dispatch('POST', $route, ['_csrf'=>$csrf->token()], $files);
    return (string) $controller->dispatch('GET', $route);
};

$cnacsInShippersHtml = $postUpload($makeFiles([
    'vehicle_load_report'=>'vehicle_load_report',
    'shippers'=>'cnacs',
    'cnacs'=>'cnacs',
]));
$callsAfterFirstError = [$previewer->calls, $extractor->calls];
$shippersInVehicleHtml = $postUpload($makeFiles([
    'vehicle_load_report'=>'shippers',
    'shippers'=>'shippers',
    'cnacs'=>'cnacs',
]));
$unknownFiles = $makeFiles([
    'vehicle_load_report'=>'vehicle_load_report',
    'shippers'=>'shippers',
    'cnacs'=>'cnacs',
]);
file_put_contents($unknownFiles['cnacs']['tmp_name'], "VIN,Dato\nX,uno\n");
$unknownFiles['cnacs']['size'] = filesize($unknownFiles['cnacs']['tmp_name']);
$unknownHtml = $postUpload($unknownFiles);

$ambiguousFiles = $makeFiles([
    'vehicle_load_report'=>'vehicle_load_report',
    'shippers'=>'shippers',
    'cnacs'=>'cnacs',
]);
file_put_contents(
    $ambiguousFiles['cnacs']['tmp_name'],
    "VIN,fdLoadId,fdTrack,fdDestinationLocation,InvoiceNo,NumRemesa,BrokerId\nX,1,2,3,4,5,6\n",
);
$ambiguousFiles['cnacs']['size'] = filesize($ambiguousFiles['cnacs']['tmp_name']);
$ambiguousHtml = $postUpload($ambiguousFiles);

$validFiles = $makeFiles([
    'vehicle_load_report'=>'vehicle_load_report',
    'shippers'=>'shippers',
    'cnacs'=>'cnacs',
], 624);
$controller->dispatch('POST', $route, ['_csrf'=>$csrf->token()], $validFiles);
$validToken = (string) array_key_last($session['_consist_rail_uploads'] ?? []);
$validHtml = (string) $controller->dispatch('GET', $route + ['preview'=>$validToken]);
$controller->dispatch('POST', $route, [
    '_csrf'=>$csrf->token(),
    'action'=>'analyze_vin_cross',
    'batch_token'=>$validToken,
]);
$analysis = $analysisStore->load($validToken);

$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('CNACS en Shippers vuelve por PRG con mensaje específico', http_response_code() === 303
    && str_contains($cnacsInShippersHtml, 'parece ser un archivo “CNACS”'));
$test('archivo incorrecto no llama vista previa completa ni extractor VIN', $callsAfterFirstError === [0, 0]);
$test('Shippers en Vehicle Load vuelve con mensaje específico', str_contains($shippersInVehicleHtml, 'parece ser un archivo “Shippers”'));
$test('estructura desconocida vuelve al formulario', str_contains($unknownHtml, 'no coincide con ninguna estructura reconocida'));
$test('archivo ambiguo vuelve al formulario', str_contains($ambiguousHtml, 'identificar de forma segura'));
$test('errores esperados no conservan previews parciales', count($session['_consist_rail_uploads'] ?? []) === 1);
$test('archivo correcto sí ejecuta vista previa completa', $previewer->calls === 1 && str_contains($validHtml, 'Los tres archivos cumplen'));
$test('extractor completo solo se ejecuta después de identidad válida', $extractor->calls === 3);
$test('flujo correcto conserva los 624 VIN por archivo', is_array($analysis)
    && ($analysis['files']['vehicle_load_report']['unique_vins'] ?? 0) === 624
    && ($analysis['files']['shippers']['unique_vins'] ?? 0) === 624
    && ($analysis['files']['cnacs']['unique_vins'] ?? 0) === 624);
$test('errores esperados no producen respuesta 500', http_response_code() !== 500);

foreach (array_keys($session['_consist_rail_uploads'] ?? []) as $token) {
    $store->discard((string) $token);
}
foreach ($paths as $path) {
    if (is_file($path)) unlink($path);
}
if (is_dir($root)) rmdir($root);
echo "\nResumen Consist Rail Validation Order: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
