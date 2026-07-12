<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\DashboardController;
use App\Controllers\InventarioController;
use App\Controllers\VerificadorController;

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