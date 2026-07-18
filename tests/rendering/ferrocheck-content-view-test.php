<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$view = $root . '/app/Views/inventario/partials/ferrocheck-content.php';
$legacyView = $root . '/app/Views/inventario/importar.php';
$javascript = $root . '/public/assets/js/importador.js';
$passed = 0;
$failed = 0;

function report(string $label, bool $condition): void
{
    global $passed, $failed;
    $condition ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $condition ? 'PASS' : 'FAIL', $label);
}

function renderContent(string $view, string $section): string
{
    $ferroSeccion = $section;
    ob_start();
    require $view;
    return (string) ob_get_clean();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/Ferrocheck/public');
}

$source = is_file($view) ? file_get_contents($view) : false;
$source = is_string($source) ? $source : '';
$legacySource = (string) file_get_contents($legacyView);
$jsSource = (string) file_get_contents($javascript);
$bufferLevel = ob_get_level();
$dashboard = renderContent($view, 'dashboard');
$import = renderContent($view, 'importar-excel');
$results = renderContent($view, 'consulta-vin');
$config = renderContent($view, 'configuracion');
$all = $dashboard . $import . $results . $config;

$_GET = ['modulo' => 'ferrocheck', 'seccion' => 'importar-excel'];
ob_start();
require $legacyView;
$legacyHtml = (string) ob_get_clean();

report('El archivo existe', is_file($view));
report('La vista es PHP ejecutable', str_contains($source, '<?php') && $all !== '');
report('Produce HTML no vacío', trim($dashboard) !== '');
report('No contiene doctype', stripos($source, '<!doctype') === false);
report('No contiene etiqueta html', !preg_match('/<\/?html\b/i', $source));
report('No contiene etiqueta head', !preg_match('/<\/?head\b/i', $source));
report('No contiene etiqueta body', !preg_match('/<\/?body\b/i', $source));
report('No contiene header global', !str_contains($source, 'class="topbar"'));
report('No contiene sidebar global', !str_contains($source, 'id="sidebarNav"'));
report('No contiene footer global', !str_contains($source, 'id="footer"'));
report('No incluye app-shell.css', !str_contains($source, 'app-shell.css'));
report('No incluye app-shell.js', !str_contains($source, 'app-shell.js'));
report('No incluye importador.css', !str_contains($source, 'importador.css'));
report('No incluye importador.js', !str_contains($source, 'importador.js'));
report('No contiene etiquetas script', stripos($source, '<script') === false);
report('Conserva el contenedor FerroCheck', str_contains($all, 'aria-label="FerroCheck"'));
report('Conserva IDs críticos del importador', preg_match_all('/id="(?:importador|dropzone|fileInput|fileInfo|fileName|fileSize|fileType|recordCount|fileStatus|progressPercent|progressFill|statusMessage|importBtn)"/', $import) === 13);
report('Conserva formulario crítico', str_contains($import, 'class="importador-form"') && str_contains($import, 'method="post"'));
report('Conserva botones críticos', str_contains($import, 'id="importBtn"') && str_contains($results, 'id="exportExcelBtn"'));
report('Conserva tabla y áreas de resultados', str_contains($results, 'id="resultados"') && str_contains($results, 'class="results-table"'));
report('Los modales dinámicos permanecen en importador.js', !str_contains($source, 'detallePlataformaModal') && str_contains($jsSource, "modal.id = 'detallePlataformaModal'"));
report('No produce salida fuera del buffer', ob_get_level() === $bufferLevel);
report('Puede incluirse desde importar.php', str_contains($legacyHtml, 'id="importador"'));
report('importar.php usa include estático', str_contains($legacySource, "require __DIR__ . '/partials/ferrocheck-content.php';"));
report('importar.php no conserva otra copia', substr_count($legacySource, 'aria-label="FerroCheck"') === 0);
report('Documento legacy conserva una etiqueta html', preg_match_all('/<html\b/i', $legacyHtml) === 1);
report('Documento legacy conserva una etiqueta head', preg_match_all('/<head\b/i', $legacyHtml) === 1);
report('Documento legacy conserva una etiqueta body', preg_match_all('/<body\b/i', $legacyHtml) === 1);
report('Contenido FerroCheck aparece una vez', substr_count($legacyHtml, 'aria-label="FerroCheck"') === 1);
report('No usa vistaActual', !str_contains($legacySource, 'vistaActual') && !str_contains($source, 'vistaActual'));
report('No usa get_defined_vars', !str_contains($source, 'get_defined_vars'));
report('No usa GLOBALS', !str_contains($source, '$GLOBALS'));
report('No accede a base de datos', !preg_match('/\b(?:PDO|mysqli|DB_HOST|DB_NAME)\b/', $source));
report('La prueba no ejecuta POST', ($_SERVER['REQUEST_METHOD'] ?? 'CLI') !== 'POST');

echo "\nResumen: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
