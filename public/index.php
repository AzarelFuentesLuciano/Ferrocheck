<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

if (($_GET['modulo'] ?? '') === 'control-escaneres') {
    $controller = new ControlEscaneresWebController(
        new ControlEscaneresServiceFactory(),
        new SessionAuthenticatedActorProvider($_SESSION, $_SERVER['REMOTE_ADDR'] ?? null),
        new SessionCsrfTokenManager($_SESSION),
        new BusinessRequestContextFactory($_SERVER, session_id()),
        new FlashMessageStore($_SESSION),
        new ControlEscaneresErrorMapper(),
    );
    $controller->dispatch($_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
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
