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
use App\Auth\{Authorization, Csrf, OrganizationalAccess, ForbiddenException, ProtectedUserPolicy};
use App\Controllers\{AdministrationController, AuthController};
use App\Controllers\Rail\RailController;
use App\Core\Database;
use App\Repositories\{AuthRepository, OrganizationalAccessRepository, OrganizationalAdminRepository, RoleAdminRepository, UserAdminRepository};
use App\Repositories\Rail\ConsistRepository;
use App\Services\{AuthService, GeneralAuditService, ModuleNavigationBuilder, OrganizationalAdminService, RoleAdminService, UserAdminService};
use App\Services\Rail\Consist\{ConsistAnalysisResultStore, ConsistDocumentBuilder, ConsistGoldenMasterComparator, ConsistSpreadsheetPreviewer, ConsistTemporaryUploadStore, ConsistUploadValidator, ConsistVinCrossAnalyzer, ConsistVinExtractor, ConsistWorkflowService, ConsistWorkbookExporter, ConsistWorkbookValidator, RouteCodeCatalogLoader};
use App\Support\Rail\RailFlashStore;

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
$moduleNavigationBuilder = $organizationalAccess
    ? new ModuleNavigationBuilder($organizationalAccess)
    : null;

if (($_GET['modulo'] ?? '') === 'administracion') {
    try{$organizationalAccess?->requireModuleAccess('administracion');}catch(ForbiddenException){http_response_code(403);require __DIR__.'/../app/Views/auth/403.php';return;}
    $userRepository = new UserAdminRepository($pdo);
    $roleRepository = new RoleAdminRepository($pdo);
    $organizationalRepository = new OrganizationalAdminRepository($pdo);
    $audit = new GeneralAuditService($pdo);
    $protectedUserPolicy = new ProtectedUserPolicy();
    (new AdministrationController(
        new Authorization($currentUser),
        new Csrf($_SESSION),
        $userRepository,
        $roleRepository,
        $organizationalRepository,
        new UserAdminService($userRepository, $authRepository, $audit, $organizationalRepository, $protectedUserPolicy),
        new RoleAdminService($roleRepository, $audit),
        new OrganizationalAdminService($organizationalRepository, $audit, $userRepository, $protectedUserPolicy),
        $protectedUserPolicy,
        $moduleNavigationBuilder,
        $_SESSION,
    ))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST);
    return;
}

if (($_GET['modulo'] ?? '') === 'rail') {
    try {
        $organizationalAccess?->requireModuleAccess('rail', 'rail.ver');
    } catch (ForbiddenException) {
        http_response_code(403);
        require __DIR__ . '/../app/Views/auth/403.php';
        return;
    }
    $railCsrf = new Csrf($_SESSION);
    $consistConfiguration = require __DIR__ . '/../config/consist-rail-import.php';
    $consistTemporaryStore = new ConsistTemporaryUploadStore(
        dirname(__DIR__) . '/storage/rail/consist/temp',
        $_SESSION,
        $currentUser->id,
        session_id(),
        (int) $consistConfiguration['expires_seconds'],
    );
    $goldenOrderPath = __DIR__ . '/../docs/rail/consist/golden-master/vin-order.json';
    $goldenOrderPayload = is_file($goldenOrderPath)
        ? (json_decode((string) file_get_contents($goldenOrderPath), true) ?: [])
        : [];
    $goldenOrder = [];
    foreach (($goldenOrderPayload['rows'] ?? []) as $referenceRow) {
        if (isset($referenceRow['vin'], $referenceRow['consist'])) {
            $goldenOrder[(string) $referenceRow['vin']] = (int) $referenceRow['consist'];
        }
    }
    $railController = new RailController(
        $currentUser,
        $railCsrf->token(),
        $moduleNavigationBuilder,
        null,
        null,
        $railCsrf,
        new RailFlashStore($_SESSION),
        $consistTemporaryStore,
        new ConsistUploadValidator($consistConfiguration),
        new ConsistSpreadsheetPreviewer($consistConfiguration),
        new ConsistVinExtractor($consistConfiguration),
        new ConsistVinCrossAnalyzer((int) $consistConfiguration['analysis_sample_limit']),
        new ConsistAnalysisResultStore($consistTemporaryStore, $currentUser->id, session_id()),
        null,
        new ConsistWorkflowService(
            new ConsistDocumentBuilder($goldenOrder),
            new RouteCodeCatalogLoader(
                __DIR__ . '/../docs/rail/consist/referencias/vascor_sm_db.xlsx',
            ),
            new ConsistRepository($pdo),
            defined('APP_ENV') && APP_ENV !== 'production'
                ? new ConsistGoldenMasterComparator(
                    __DIR__ . '/../docs/rail/consist/golden-master/consist-output.json',
                )
                : null,
            new ConsistWorkbookExporter(
                __DIR__ . '/../docs/rail/consist/referencias/Consist Rail del 29 al 30 de Julio de 2026.xlsx',
            ),
            new ConsistWorkbookValidator(),
            dirname(__DIR__) . '/storage/rail/consist/exports',
        ),
    );
    $response = $railController->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_GET,
        $_POST,
        $_FILES,
    );
    if (is_string($response)) {
        echo $response;
    }
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
        $currentUser,
        (new Csrf($_SESSION))->token(),
        $moduleNavigationBuilder,
    );
    $controller->dispatch($_GET, $_POST, $_FILES, $_SERVER['REQUEST_METHOD'] ?? 'GET');
    return;
}

if (($_GET['modulo'] ?? '') === 'operaciones-patio') {
    $controller = new OperacionPatioController(
        $currentUser,
        (new Csrf($_SESSION))->token(),
        $moduleNavigationBuilder,
    );
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
$controller->setAuthenticatedUser($currentUser, (new Csrf($_SESSION))->token(), $moduleNavigationBuilder);
$controller->index();
