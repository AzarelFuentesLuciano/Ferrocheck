<?php

declare(strict_types=1);

$controllerPath = __DIR__ . '/../../app/Controllers/DashboardController.php';
$entryPath = __DIR__ . '/../../public/index.php';
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

$renderAssignment = strpos($source, '$html = $this->renderAppShell();');
$fallbackCall = strpos($source, '$this->renderLegacy();', (int) $renderAssignment);
$htmlOutput = strpos($source, 'echo $html;', (int) $renderAssignment);

$test('Existe bandera local de renderizado', preg_match("/private const RENDER_MODE\\s*=\\s*'[^']+';/", $source) === 1);
$test('El modo predeterminado es legacy', str_contains($source, "private const RENDER_MODE = 'legacy';"));
$test('Sólo se declaran legacy y app_shell', str_contains($source, "private const ALLOWED_RENDER_MODES = ['legacy', 'app_shell'];"));
$test('La bandera no se obtiene de GET', preg_match('/RENDER_MODE[^;]*\\$_GET/s', $source) !== 1);
$test('La bandera no se obtiene de POST', preg_match('/RENDER_MODE[^;]*\\$_POST/s', $source) !== 1);
$test('La bandera no se obtiene de SESSION', preg_match('/RENDER_MODE[^;]*\\$_SESSION/s', $source) !== 1);
$test('La bandera no se obtiene de cookies', preg_match('/RENDER_MODE[^;]*\\$_COOKIE/s', $source) !== 1);
$test('Existe camino legacy explícito', str_contains($source, 'private function renderLegacy(): void'));
$test('El camino legacy conserva importar.php', str_contains($source, "require __DIR__ . '/../Views/inventario/importar.php';"));
$test('Existe retorno después del render legacy', preg_match('/renderLegacy\(\);\s*return;/s', $source) === 1);
$test('No usa get_defined_vars', !str_contains($source, 'get_defined_vars'));
$test('No usa GLOBALS', !str_contains($source, '$GLOBALS'));
$test('No pasa importar.php como contenido', preg_match('/contenidoModulo[^\n]*importar\.php/', $source) !== 1);
$test('Referencia LegacyRenderBridge', str_contains($source, 'new LegacyRenderBridge()'));
$test('Referencia RenderAdapter', str_contains($source, 'new RenderAdapter()'));
$test('Construye HTML antes de emitirlo', $renderAssignment !== false && $htmlOutput !== false && $renderAssignment < $htmlOutput);
$test('Maneja RenderException', str_contains($source, 'catch (RenderException)'));
$test('Fallback sucede antes de cualquier echo del HTML nuevo', $fallbackCall !== false && $htmlOutput !== false && $fallbackCall < $htmlOutput);
$test('El camino app_shell está bloqueado sin contenido', str_contains($source, "if (\$legacy['contenidoModulo'] === '')"));
$test('El bloqueo ocurre antes de crear el bridge', strpos($source, "if (\$legacy['contenidoModulo'] === '')") < strpos($source, 'new LegacyRenderBridge()'));
$test('El modo desconocido cae de forma segura a legacy', preg_match("/\? self::RENDER_MODE\s*:\s*'legacy'/s", $source) === 1);
$test('El punto de entrada conserva DashboardController', str_contains($entrySource, '$controller = new DashboardController();') && str_contains($entrySource, '$controller->index();'));
$test('No se activa app_shell por defecto', !str_contains($source, "private const RENDER_MODE = 'app_shell';"));

echo "\nResumen Dashboard Render Mode: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
