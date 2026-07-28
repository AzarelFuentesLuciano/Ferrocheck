<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Auth\Csrf;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Controllers\Rail\RailController;
use App\Services\ModuleNavigationBuilder;
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;
use App\Services\Rail\Consist\ConsistUploadValidator;
use App\Support\Rail\RailFlashStore;

final class ConsistFlowAccessRepository implements OrganizationalAccessRepositoryInterface
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
$tokenBefore = $csrf->token();
$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consist_flow_' . bin2hex(random_bytes(5));
$store = new ConsistTemporaryUploadStore(
    $root,
    $session,
    10,
    'flow-session',
    1800,
    static fn (string $source, string $target): bool => copy($source, $target),
);
$validator = new ConsistUploadValidator($configuration, static fn (string $path): bool => is_file($path));
$user = new AuthenticatedUser(10, 'Usuario Rail', 'rail', ['Administrador'], ['rail.ver']);
$controller = new RailController(
    $user,
    $tokenBefore,
    new ModuleNavigationBuilder(new OrganizationalAccess($user, new ConsistFlowAccessRepository())),
    null,
    '/Ferrocheck/public',
    $csrf,
    new RailFlashStore($session),
    $store,
    $validator,
    new ConsistSpreadsheetPreviewer($configuration),
);
$paths = [];
$files = [];
foreach (['vehicle_load_report', 'shippers', 'cnacs'] as $field) {
    $path = tempnam(sys_get_temp_dir(), 'crf_');
    file_put_contents($path, "Notas\nVIN\n " . strtoupper($field) . " \n");
    $paths[] = $path;
    $files[$field] = ['name'=>$field . '.csv','tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];
}
$controller->dispatch('POST', ['seccion'=>'consist-rail','subseccion'=>'registrar'], ['_csrf'=>'incorrecto'], $files);
$csrfRejected = ($session['_consist_rail_uploads'] ?? []) === [];
$missingFiles = $files;
unset($missingFiles['cnacs']);
$controller->dispatch('POST', ['seccion'=>'consist-rail','subseccion'=>'registrar'], ['_csrf'=>$tokenBefore], $missingFiles);
$missingRejected = ($session['_consist_rail_uploads'] ?? []) === [];
$invalidFiles = $files;
$invalidFiles['cnacs']['name'] = 'cnacs.exe';
$controller->dispatch('POST', ['seccion'=>'consist-rail','subseccion'=>'registrar'], ['_csrf'=>$tokenBefore], $invalidFiles);
$extensionRejected = ($session['_consist_rail_uploads'] ?? []) === [];
$controller->dispatch('POST', ['seccion'=>'consist-rail','subseccion'=>'registrar'], ['_csrf'=>$tokenBefore], $files);
$entries = $session['_consist_rail_uploads'] ?? [];
$entry = reset($entries);
$previewToken = is_array($entry) ? (string) ($entry['token'] ?? '') : '';
$sessionBeforeGet = serialize($session['_consist_rail_uploads'] ?? []);
$html = $controller->dispatch('GET', ['seccion'=>'consist-rail','subseccion'=>'registrar','preview'=>$previewToken]);
$sessionAfterGet = serialize($session['_consist_rail_uploads'] ?? []);

$passed = 0;
$failed = 0;
$test = static function (string $name, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $name);
};
$test('POST aplica PRG con HTTP 303', http_response_code() === 303);
$test('POST sin CSRF no crea lote', $csrfRejected);
$test('POST con archivo faltante no crea lote', $missingRejected);
$test('POST con extensión inválida no crea lote', $extensionRejected);
$test('CSRF rota después de una carga válida', $csrf->token() !== $tokenBefore);
$test('lote queda ligado a sesión sin rutas en el HTML', $previewToken !== '' && !str_contains((string) $html, $root));
$test('GET renderiza los tres campos requeridos', substr_count((string) $html, 'type="file"') === 3);
$test('vista muestra resultados por los tres archivos', substr_count((string) $html, 'Registros válidos') === 3);
$test('refrescar GET no reprocesa ni altera el lote', $sessionBeforeGet === $sessionAfterGet);
$test('conserva App Shell y navegación Rail', str_contains((string) $html, 'data-app-shell') && str_contains((string) $html, 'class="rail-navigation"'));
$test('no ofrece persistencia o salida final', !str_contains((string) $html, 'Generar Summary') && !str_contains((string) $html, 'Enviar correo'));

if ($previewToken !== '') { $store->discard($previewToken); }
foreach ($paths as $path) { if (is_file($path)) { unlink($path); } }
if (is_dir($root)) { rmdir($root); }
echo "\nResumen Consist Rail Flow: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
