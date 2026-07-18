<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/Controllers/DashboardController.php';
$entryPath = $root . '/public/index.php';
$source = file_get_contents($controllerPath);
$entrySource = file_get_contents($entryPath);

if (!is_string($source) || !is_string($entrySource)) {
    fwrite(STDERR, "No fue posible leer los archivos bajo prueba.\n");
    exit(1);
}

$passed = 0;
$failed = 0;

$test = static function (string $label, bool $condition) use (&$passed, &$failed): void {
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
};

preg_match('/private function isFerroCheckRequest\([^}]+\}/s', $source, $identificationMatch);
preg_match('/private function shouldRenderFerroCheckWithAppShell\([^}]+\}/s', $source, $decisionMatch);
$identificationSource = $identificationMatch[0] ?? '';
$decisionSource = $decisionMatch[0] ?? '';

require_once $root . '/vendor/autoload.php';

$controller = new App\Controllers\DashboardController();
$identify = new ReflectionMethod($controller, 'isFerroCheckRequest');
$decide = new ReflectionMethod($controller, 'shouldRenderFerroCheckWithAppShell');
$sections = ['dashboard', 'consulta-vin', 'importar-excel', 'busqueda-multiple', 'configuracion'];
$allSectionsRecognized = true;
foreach ($sections as $section) {
    $allSectionsRecognized = $allSectionsRecognized && $identify->invoke($controller, 'ferrocheck', $section);
}

$initialLevel = ob_get_level();
ob_start();
$dashboardDecision = $decide->invoke($controller, 'dashboard', '');
$ferroCheckDecision = $decide->invoke($controller, 'ferrocheck', 'consulta-vin');
$scannerDecision = $decide->invoke($controller, 'control-escaneres', 'dashboard');
$unknownDecision = $decide->invoke($controller, 'desconocido', '');
$lateralOutput = (string) ob_get_clean();

$test('Existe FERROCHECK_APP_SHELL_ENABLED', str_contains($source, 'FERROCHECK_APP_SHELL_ENABLED'));
$test('Bandera predeterminada es false', str_contains($source, 'private const FERROCHECK_APP_SHELL_ENABLED = false;'));
$test('Bandera es privada', preg_match('/private const FERROCHECK_APP_SHELL_ENABLED/', $source) === 1);
$test('Bandera es constante', preg_match('/const FERROCHECK_APP_SHELL_ENABLED/', $source) === 1);
$test('Existe método para identificar FerroCheck', $identificationSource !== '');
$test('Existe método para decidir App Shell', $decisionSource !== '');
$test('Decisión requiere la bandera', str_contains($decisionSource, 'self::FERROCHECK_APP_SHELL_ENABLED'));
$test('Decisión requiere identificar FerroCheck', str_contains($decisionSource, '$this->isFerroCheckRequest($modulo, $seccion)'));
$test('Dashboard no es FerroCheck', $identify->invoke($controller, 'dashboard', '') === false);
$test('Control de Escáneres no es FerroCheck', $identify->invoke($controller, 'control-escaneres', 'dashboard') === false);
$test('Inventario de Material no es FerroCheck', $identify->invoke($controller, 'inventario-material', '') === false);
$test('Módulo desconocido no es FerroCheck', $identify->invoke($controller, 'ferrocheck-extra', 'dashboard') === false);
$test('Secciones reales de FerroCheck son reconocidas', $allSectionsRecognized);
$test('Bandera false mantiene FerroCheck en legacy', $ferroCheckDecision === false);
$test('Bandera false mantiene Dashboard en legacy', $dashboardDecision === false);
$test('Bandera false mantiene Escáneres en legacy', $scannerDecision === false);
$test('No existe activación por GET', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*\$_GET/s', $source));
$test('No existe activación por POST', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*\$_POST/s', $source));
$test('No existe activación por sesión', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*\$_SESSION/s', $source));
$test('No existe activación por cookie', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*\$_COOKIE/s', $source));
$test('No existe activación por headers', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*(?:HTTP_|getallheaders|apache_request_headers)/s', $source));
$test('No existe activación por variable de entorno', !preg_match('/FERROCHECK_APP_SHELL_ENABLED[^;]*(?:getenv|\$_ENV)/s', $source));
$test('Compuerta no consulta base de datos', !preg_match('/(?:PDO|mysqli|DashboardService|DB_)/', $identificationSource . $decisionSource));
$test('No usa coincidencia genérica insegura', !preg_match('/\b(?:strpos|stripos|str_contains|preg_match)\s*\(/', $identificationSource));
$test('No usa vistaActual', !str_contains($source, 'vistaActual'));
$test('Pipeline existente permanece disponible', str_contains($source, 'private function renderAppShell(string $ferroSeccion): string'));
$test('Fallback legacy continúa presente', str_contains($source, "require __DIR__ . '/../Views/inventario/importar.php';"));
$test('importar.php es flujo activo con bandera false', str_contains($source, 'if (!$this->shouldRenderFerroCheckWithAppShell($modulo, $seccion))'));
$test('RENDER_MODE no activa otros módulos', $dashboardDecision === false && $scannerDecision === false && $unknownDecision === false);
$test('No existe rama App Shell para Escáneres', !preg_match('/renderAppShell[^;]*control-escaneres/s', $source));
$test('No existe rama App Shell para Dashboard', !preg_match('/renderAppShell[^;]*modulo=dashboard/s', $source));
$test('Rutas públicas permanecen intactas', str_contains($entrySource, '$controller = new DashboardController();') && str_contains($entrySource, '$controller->index();'));
$test('No existe salida lateral', $lateralOutput === '' && ob_get_level() === $initialLevel);
$test('Prueba define salida exit correcta', str_contains((string) file_get_contents(__FILE__), 'exit($failed === 0 ? 0 : 1);'));

echo "\nResumen FerroCheck App Shell Gate: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
