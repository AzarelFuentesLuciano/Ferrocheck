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
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;
use App\Services\Rail\Consist\ConsistUploadValidator;
use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;
use App\Services\Rail\Consist\ConsistVinExtractor;
use App\Support\Rail\RailFlashStore;

final class ConsistPhase2AccessRepository implements OrganizationalAccessRepositoryInterface
{
    public function findActiveModule(string $moduleKey): ?array { return $moduleKey === 'rail' ? $this->visibleActiveModules()[0] : null; }
    public function individualModuleDecision(int $userId, int $moduleId): ?string { return null; }
    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool { return true; }
    public function activeAreaIdsForUser(int $userId): array { return [1]; }
    public function activeAreaExists(int $areaId): bool { return true; }
    public function visibleActiveModules(): array
    {
        return [['id'=>2,'clave'=>'rail','nombre'=>'Rail','descripcion'=>'','ruta'=>'rail','icono'=>'R','orden'=>10]];
    }
    public function userHasActiveArea(int $userId): bool { return true; }
    public function userHasActiveModuleDecision(int $userId): bool { return false; }
}

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$session = [];
$csrf = new Csrf($session);
$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consist_phase2_' . bin2hex(random_bytes(5));
$mover = static fn (string $source, string $target): bool => copy($source, $target);
$temporaryStore = new ConsistTemporaryUploadStore($root, $session, 10, 'phase2-session', 1800, $mover);
$user = new AuthenticatedUser(10, 'Usuario Rail', 'rail', ['Administrador'], ['rail.ver']);
$controller = new RailController(
    $user,
    $csrf->token(),
    new ModuleNavigationBuilder(new OrganizationalAccess($user, new ConsistPhase2AccessRepository())),
    null,
    '/Ferrocheck/public',
    $csrf,
    new RailFlashStore($session),
    $temporaryStore,
    new ConsistUploadValidator($configuration, static fn (string $path): bool => is_file($path)),
    new ConsistSpreadsheetPreviewer($configuration),
    new ConsistVinExtractor($configuration),
    new ConsistVinCrossAnalyzer((int) $configuration['analysis_sample_limit']),
    new ConsistAnalysisResultStore($temporaryStore, 10, 'phase2-session'),
);
$route = ['seccion'=>'consist-rail','subseccion'=>'registrar'];
$paths = [];
$makeFiles = static function (bool $valid) use (&$paths): array {
    $values = [
        'vehicle_load_report'=>['ALL','ONLY-V','VS','VC'],
        'shippers'=>['ALL','ONLY-S','VS','SC'],
        'cnacs'=>['ALL','ONLY-C','VC','SC'],
    ];
    $files = [];
    foreach ($values as $field => $vins) {
        $path = tempnam(sys_get_temp_dir(), 'phase2_file_');
        $headers = [
            'vehicle_load_report' => 'fdWholeVIN,fdTransportationName1,fdLoadId,fdTrack,fdDestinationLocation',
            'shippers' => 'fdWholeVIN1,fdPedimentoType,fdPortCode,fdBillNumber,fdBillDate',
            'cnacs' => 'VIN,InvoiceNo,NumRemesa,BrokerId,H/SCODE',
        ];
        $header = $valid ? $headers[$field] : 'OTRA,DATO1,DATO2,DATO3,DATO4';
        $rows = array_map(static fn (string $vin): string => "{$vin},uno,dos,tres,cuatro", $vins);
        file_put_contents($path, $header . "\n" . implode("\n", $rows) . "\n");
        $paths[] = $path;
        $files[$field] = ['name'=>$field.'.csv','tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];
    }
    return $files;
};

$initialHtml = $controller->dispatch('GET', $route);
$invalidCsrf = $csrf->token();
$controller->dispatch('POST', $route, ['_csrf'=>$invalidCsrf], $makeFiles(false));
$invalidToken = (string) array_key_last($session['_consist_rail_uploads'] ?? []);
$invalidHtml = $controller->dispatch('GET', $route + ['preview'=>$invalidToken]);

$validCsrf = $csrf->token();
$controller->dispatch('POST', $route, ['_csrf'=>$validCsrf], $makeFiles(true));
$validToken = (string) array_key_last($session['_consist_rail_uploads'] ?? []);
$validHtml = $controller->dispatch('GET', $route + ['preview'=>$validToken]);

$analysisCsrf = $csrf->token();
$controller->dispatch('POST', $route, ['_csrf'=>'incorrecto','action'=>'analyze_vin_cross','batch_token'=>$validToken]);
$csrfRejected = !is_file($root . DIRECTORY_SEPARATOR . $validToken . DIRECTORY_SEPARATOR . 'vin-analysis.json');
$controller->dispatch('POST', $route, ['_csrf'=>$analysisCsrf,'action'=>'analyze_vin_cross']);
$missingTokenRejected = !is_file($root . DIRECTORY_SEPARATOR . $validToken . DIRECTORY_SEPARATOR . 'vin-analysis.json');
$controller->dispatch('POST', $route, ['_csrf'=>$analysisCsrf,'action'=>'analyze_vin_cross','batch_token'=>str_repeat('f', 64)]);
$invalidTokenRejected = !is_file($root . DIRECTORY_SEPARATOR . $validToken . DIRECTORY_SEPARATOR . 'vin-analysis.json');
$controller->dispatch('POST', $route, ['_csrf'=>$analysisCsrf,'action'=>'analyze_vin_cross','batch_token'=>$validToken]);
$statusAfterPost = http_response_code();
$analysisPath = $root . DIRECTORY_SEPARATOR . $validToken . DIRECTORY_SEPARATOR . 'vin-analysis.json';
$analysisHash = is_file($analysisPath) ? hash_file('sha256', $analysisPath) : '';
$resultHtml = $controller->dispatch('GET', $route + ['preview'=>$validToken]);
$refreshHtml = $controller->dispatch('GET', $route + ['preview'=>$validToken]);
$refreshHash = is_file($analysisPath) ? hash_file('sha256', $analysisPath) : '';

$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('botón ausente sin lote', !str_contains((string) $initialHtml, 'Analizar cruce de VIN'));
$test('botón ausente con lote inválido', !str_contains((string) $invalidHtml, 'Analizar cruce de VIN'));
$test('botón presente con tres archivos válidos', str_contains((string) $validHtml, 'Analizar cruce de VIN'));
$test('POST de análisis sin CSRF es rechazado', $csrfRejected);
$test('POST de análisis sin token es rechazado', $missingTokenRejected);
$test('POST de análisis con token inexistente es rechazado', $invalidTokenRejected);
$test('POST válido responde 303', $statusAfterPost === 303);
$test('resultado aparece en GET', str_contains((string) $resultHtml, 'Cruce completado') && str_contains((string) $resultHtml, 'Total único combinado'));
$test('renderiza las siete categorías', substr_count((string) $resultHtml, 'rail-consist-cross-card--') === 7);
$test('refrescar GET no repite análisis', $analysisHash !== '' && $analysisHash === $refreshHash && str_contains((string) $refreshHtml, 'Cruce completado'));
$test('mantiene resultado ligado al lote', str_contains((string) $resultHtml, 'ONLY-V') && !str_contains((string) $resultHtml, $root));
$test('no consulta ni escribe base de datos', !str_contains((string) $resultHtml, 'PDO') && !str_contains((string) $resultHtml, 'SQL'));
$test('no genera Excel ni Consist final', !str_contains((string) $resultHtml, 'Generar Consist') && !str_contains((string) $resultHtml, 'Exportar Excel'));
$test('conserva App Shell y formulario sin JavaScript', str_contains((string) $resultHtml, 'data-app-shell') && str_contains((string) $resultHtml, 'name="action" value="analyze_vin_cross"'));

foreach (array_keys($session['_consist_rail_uploads'] ?? []) as $token) { $temporaryStore->discard((string) $token); }
foreach ($paths as $path) { if (is_file($path)) unlink($path); }
if (is_dir($root)) rmdir($root);
echo "\nResumen Consist Rail Phase 2 Flow: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
