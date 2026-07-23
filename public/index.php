<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

\App\Auth\SessionSecurity::start($_SERVER);
\App\Support\GlobalErrorHandler::register();

use App\Controllers\DashboardController;
use App\Controllers\DetallePlataformaController;
use App\Controllers\ExportacionInventarioController;
use App\Controllers\InventarioController;
use App\Controllers\OperacionPatioController;
use App\Controllers\VerificadorController;
use App\Controllers\ControlEscaneres\ControlEscaneresWebController;
use App\Factories\ControlEscaneresServiceFactory;
use App\Security\ControlEscaneres\{SessionAuthenticatedActorProvider, SessionCsrfTokenManager};
use App\Support\ControlEscaneres\{BusinessRequestContextFactory, ControlEscaneresErrorMapper, FlashMessageStore};
use App\Auth\{Authorization, Csrf, OrganizationalAccess, ForbiddenException};
use App\Controllers\{AdministrationController, AuthController};
use App\Core\Database;
use App\Repositories\{AuthRepository, OrganizationalAccessRepository, OrganizationalAdminRepository, RoleAdminRepository, UserAdminRepository};
use App\Services\{AuthService, GeneralAuditService, ModuleNavigationBuilder, OrganizationalAdminService, RoleAdminService, UserAdminService};

if (($_GET['modulo'] ?? '') === 'auth') {
    $pdo = Database::getConnection();
    $authRepository = new AuthRepository($pdo);
    (new AuthController(
        new AuthService($authRepository, $_SESSION),
        new Csrf($_SESSION),
        new GeneralAuditService($pdo),
        $_SESSION,
    ))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST, $_SERVER);
    return;
}

$pdo = Database::getConnection();
$authRepository = new AuthRepository($pdo);
$authService = new AuthService($authRepository, $_SESSION);
$currentUser = $authService->current();
$requestedReturn = $authService->safeReturn((string) ($_SERVER['REQUEST_URI'] ?? ''));
if ($currentUser === null) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . BASE_URL . '/index.php?modulo=auth&return=' . rawurlencode($requestedReturn), true, 302);
    return;
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$organizationalAccess = $currentUser ? new OrganizationalAccess($currentUser, new OrganizationalAccessRepository($pdo)) : null;
if ($organizationalAccess) $_SESSION['auth_module_keys'] = array_column((new ModuleNavigationBuilder($organizationalAccess))->build((string)BASE_URL), 'key');
else unset($_SESSION['auth_module_keys']);

if (($_GET['modulo'] ?? '') === 'administracion') {
    try{$organizationalAccess?->requireModuleAccess('administracion');}catch(ForbiddenException){http_response_code(403);require __DIR__.'/../app/Views/auth/403.php';return;}
    $userRepository = new UserAdminRepository($pdo);
    $roleRepository = new RoleAdminRepository($pdo);
    $organizationalRepository = new OrganizationalAdminRepository($pdo);
    $audit = new GeneralAuditService($pdo);
    (new AdministrationController(
        new Authorization($currentUser),
        new Csrf($_SESSION),
        $userRepository,
        $roleRepository,
        $organizationalRepository,
        new UserAdminService($userRepository, $authRepository, $audit, $organizationalRepository),
        new RoleAdminService($roleRepository, $audit),
        new OrganizationalAdminService($organizationalRepository, $audit),
        $_SESSION,
    ))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST);
    return;
}

if (($_GET['modulo'] ?? '') === 'control-escaneres') {
    try{$organizationalAccess?->requireModuleAccess('control_escaneres','escaneres.ver');}catch(ForbiddenException){http_response_code(403);require __DIR__.'/../app/Views/auth/403.php';return;}
    $operationPermissions = ['entrega'=>'escaneres.entregar','recepcion'=>'escaneres.recibir','registrar'=>'escaneres.crear','importar-inventario'=>'escaneres.crear','editar'=>'escaneres.editar','baja'=>'escaneres.editar','reactivar'=>'escaneres.editar','areas'=>'escaneres.editar','incidencias'=>'escaneres.editar','mantenimiento'=>'escaneres.editar'];
    $operationSection = (string) ($_GET['seccion'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($operationPermissions[$operationSection])) {
        $authorization = new Authorization($currentUser);
        if ($authorization->user() === null) {
            $return = rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? BASE_URL . '/index.php?modulo=control-escaneres&seccion=' . $operationSection));
            header('Location: ' . BASE_URL . '/index.php?modulo=auth&return=' . $return, true, 303);
            return;
        }
        if (!$authorization->can($operationPermissions[$operationSection])) {
            http_response_code(403);
            require __DIR__ . '/../app/Views/auth/403.php';
            return;
        }
    }
    $controller = new ControlEscaneresWebController(
        new ControlEscaneresServiceFactory(),
        new SessionAuthenticatedActorProvider($_SESSION, $_SERVER['REMOTE_ADDR'] ?? null),
        new SessionCsrfTokenManager($_SESSION),
        new BusinessRequestContextFactory($_SERVER, session_id()),
        new FlashMessageStore($_SESSION),
        new ControlEscaneresErrorMapper(),
    );
    $controller->dispatch($_GET, $_POST, $_FILES, $_SERVER['REQUEST_METHOD'] ?? 'GET');
    return;
}

if (($_GET['modulo'] ?? '') === 'operaciones-patio') {
    $controller = new OperacionPatioController();
    $controller->index();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_stats'])) {
    $controller = new DashboardController();
    $controller->resumenTarjetas();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_equipo'])) {
    $controller = new DetallePlataformaController();
    $controller->detalle();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'exportar_xlsx') {
    $controller = new ExportacionInventarioController();
    $controller->exportar();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipos'])) {
    $controller = new VerificadorController();
    $controller->verificar();
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $controller = new InventarioController();
    $controller->importar();
    return;
}

$controller = new DashboardController();
$controller->index();
