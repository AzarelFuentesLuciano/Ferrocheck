<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/Controllers/DashboardController.php';
$contentPath = $root . '/app/Views/inventario/partials/ferrocheck-content.php';
$entryPath = $root . '/public/index.php';
$source = file_get_contents($controllerPath);
$contentSource = file_get_contents($contentPath);
$entrySource = file_get_contents($entryPath);

if (!is_string($source) || !is_string($contentSource) || !is_string($entrySource)) {
    fwrite(STDERR, "No fue posible leer los archivos bajo prueba.\n");
    exit(1);
}

$passed = 0;
$failed = 0;

$test = static function (string $label, bool $condition) use (&$passed, &$failed): void {
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
};

$captureMethod = strpos($source, 'private function renderFerroCheckContent(string $ferroSeccion): string');
$bridgePosition = strpos($source, 'new LegacyRenderBridge()');
$renderPosition = strpos($source, '$html = $this->renderAppShell();');
$echoPosition = strpos($source, 'echo $html;', (int) $renderPosition);

$test('Existe método de captura de contenido', $captureMethod !== false);
$test('Usa ruta estática hacia ferrocheck-content.php', str_contains($source, "require __DIR__ . '/../Views/inventario/partials/ferrocheck-content.php';"));
$test('No usa importar.php como contenido', preg_match('/contenidoModulo[^\n]*importar\.php/', $source) !== 1);
$test('No usa vistaActual', !str_contains($source, 'vistaActual'));
$test('No usa get_defined_vars', !str_contains($source, 'get_defined_vars'));
$test('No usa GLOBALS', !str_contains($source, '$GLOBALS'));
$test('No usa extract', !preg_match('/\bextract\s*\(/', $source));
$test('Usa output buffering', str_contains($source, 'ob_start();') && str_contains($source, 'ob_get_clean();'));
$test('Registra nivel inicial del buffer', str_contains($source, '$initialLevel = ob_get_level();'));
$test('Limpia sólo buffers propios', str_contains($source, 'while (ob_get_level() > $initialLevel)'));
$test('Método declara retorno string', str_contains($source, 'renderFerroCheckContent(string $ferroSeccion): string'));
$test('Rechaza contenido vacío', str_contains($source, "trim(\$contenidoModulo) === ''"));
$test('Camino app_shell referencia LegacyRenderBridge', str_contains($source, 'new LegacyRenderBridge()'));
$test('Camino app_shell referencia RenderAdapter', str_contains($source, 'new RenderAdapter()'));
$test('Construye RenderContext mediante el bridge', str_contains($source, 'createContext($legacy)'));
$test('contenidoModulo recibe HTML capturado', str_contains($source, "'contenidoModulo' => \$contenidoModulo"));
$test('Pasa estilos mediante additionalStyles', str_contains($source, "'additionalStyles' => ["));
$test('Pasa scripts mediante additionalScripts', str_contains($source, "'additionalScripts' => ["));
$test('contenidoModulo no contiene link', stripos($contentSource, '<link') === false);
$test('contenidoModulo no contiene script', stripos($contentSource, '<script') === false);
$test('HTML final se guarda antes de imprimir', $renderPosition !== false && $echoPosition !== false && $renderPosition < $echoPosition);
$test('Existe retorno después del echo final', preg_match('/echo \$html;\s*return;/s', $source) === 1);
$test('Existe manejo de RenderException', str_contains($source, 'catch (RenderException)'));
$test('Existe fallback legacy', str_contains($source, 'private function renderLegacy(): void'));
$test('Fallback usa importar.php', str_contains($source, "require __DIR__ . '/../Views/inventario/importar.php';"));
$test('Fallback se invoca una vez en el catch', preg_match('/catch \(RenderException\)\s*\{\s*\$this->renderLegacy\(\);\s*return;/s', $source) === 1);
$test('No existe salida previa al render completo', $renderPosition !== false && $echoPosition !== false && $renderPosition < $echoPosition);
$test('RENDER_MODE continúa en legacy', str_contains($source, "private const RENDER_MODE = 'legacy';"));
$test('No existe activación HTTP del modo', !preg_match('/RENDER_MODE[^;]*\$_(?:GET|POST|SESSION|COOKIE|SERVER)/s', $source));
$test('Punto de entrada y rutas permanecen estructuralmente presentes', str_contains($entrySource, '$controller = new DashboardController();') && str_contains($entrySource, '$controller->index();'));

require_once $root . '/config/config.php';
require_once $root . '/vendor/autoload.php';

$_GET = ['seccion' => 'consulta-vin'];
$controller = new App\Controllers\DashboardController();
$method = new ReflectionMethod($controller, 'renderAppShell');
$externalLevel = ob_get_level();
ob_start();
$html = $method->invoke($controller);
$lateralOutput = (string) ob_get_clean();

$test('Vista capturada atraviesa bridge y adapter', is_string($html) && $html !== '');
$test('Documento final contiene una etiqueta html', preg_match_all('/<html\b/i', $html) === 1);
$test('Documento final contiene una etiqueta head', preg_match_all('/<head\b/i', $html) === 1);
$test('Documento final contiene una etiqueta body', preg_match_all('/<body\b/i', $html) === 1);
$test('Documento final contiene FerroCheck', str_contains($html, 'aria-label="FerroCheck"'));
$test('Documento final contiene assets requeridos', str_contains($html, '/assets/css/importador.css') && str_contains($html, '/assets/js/importador.js'));
$test('Documento final no contiene doble shell', substr_count($html, 'class="app-shell" data-app-shell>') === 1 && substr_count($html, '<!DOCTYPE html>') === 1);
$test('Pipeline no imprime salida lateral', $lateralOutput === '' && ob_get_level() === $externalLevel);

echo "\nResumen Dashboard App Shell Pipeline: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
