<?php

declare(strict_types=1);

/**
 * Pruebas de humo GET, deliberadamente no mutables.
 * Uso: php tests/smoke/smoke.php [base-url]
 */

$baseUrl = $argv[1] ?? getenv('VASCOR_SMOKE_BASE_URL') ?: 'http://localhost/Ferrocheck/public';
$baseUrl = rtrim((string) $baseUrl, '/');
$entryUrl = str_ends_with($baseUrl, '/index.php') ? $baseUrl : $baseUrl . '/index.php';
$publicBase = str_ends_with($baseUrl, '/index.php') ? substr($baseUrl, 0, -10) : $baseUrl;

$tests = [
    ['Dashboard general', '?modulo=dashboard', 'Resumen general', null, false],
    ['FerroCheck Dashboard', '?modulo=ferrocheck&seccion=dashboard', 'Resumen de la operación ferroviaria', 'dashboard', true],
    ['FerroCheck Consulta VIN', '?modulo=ferrocheck&seccion=consulta-vin', 'Validación de plataforma', 'consulta-vin', true],
    ['FerroCheck Importar Excel', '?modulo=ferrocheck&seccion=importar-excel', 'Importador Ferromex', 'importar-excel', true],
    ['FerroCheck Búsqueda múltiple', '?modulo=ferrocheck&seccion=busqueda-multiple', 'Validación consolidada', 'busqueda-multiple', true],
    ['FerroCheck Configuración', '?modulo=ferrocheck&seccion=configuracion', 'Preferencias de FerroCheck', 'configuracion', true],
    ['Escáneres Dashboard', '?modulo=control-escaneres&seccion=dashboard', 'Estado de la operación', 'dashboard', false],
    ['Escáneres Catálogo', '?modulo=control-escaneres&seccion=catalogo', 'Escáneres registrados', 'catalogo', false],
    ['Escáneres Expediente', '?modulo=control-escaneres&seccion=expediente', 'Información integral del equipo', 'expediente', false],
    ['Escáneres Entrega', '?modulo=control-escaneres&seccion=entrega', 'Nueva entrega', 'entrega', false],
    ['Escáneres Recepción', '?modulo=control-escaneres&seccion=recepcion', 'Recepción de escáner', 'recepcion', false],
    ['Escáneres Historial', '?modulo=control-escaneres&seccion=historial', 'Historial operativo', 'historial', false],
    ['Escáneres Reportes', '?modulo=control-escaneres&seccion=reportes', 'Consulta y análisis', 'reporte', false],
];

$passed = 0;
$failed = 0;

function requestGet(string $url): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPGET => true,
        CURLOPT_HEADER => false,
    ]);

    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $error];
}

function report(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail === '' ? '' : ' — ' . $detail);
}

function containsAll(string $html, array $markers): bool
{
    foreach ($markers as $marker) {
        if (!str_contains($html, $marker)) {
            return false;
        }
    }
    return true;
}

echo "VASCOR OPS smoke tests (GET only)\nBase: {$entryUrl}\n\n";

foreach ($tests as [$name, $query, $pageMarker, $section, $isFerrocheck]) {
    $response = requestGet($entryUrl . $query);
    $html = $response['body'];
    $isScanner = str_contains($query, 'modulo=control-escaneres');

    report($name . ': HTTP 200', $response['status'] === 200, $response['error']);
    report($name . ': sin error PHP visible', !preg_match('/(?:Fatal error|Parse error|Warning|Notice):/i', $html));
    report($name . ': shell global', containsAll($html, ['class="topbar"', 'id="sidebarNav"', 'id="footer"']));
    report($name . ': contenido clave', str_contains($html, $pageMarker), $pageMarker);
    report($name . ': estilos globales', containsAll($html, ['assets/css/importador.css', 'assets/css/vascor-design-system.css', 'family=Poppins']));

    if ($isFerrocheck) {
        report($name . ': navegación interna', str_contains($html, 'class="vascor-module-nav"'));
        $activePattern = '/vascor-module-nav__item is-active[^>]+seccion=' . preg_quote((string) $section, '/') . '/';
        report($name . ': sección activa', (bool) preg_match($activePattern, $html), (string) $section);
        report($name . ': script esperado', str_contains($html, 'assets/js/importador.js'));
    } elseif ($isScanner) {
        report($name . ': navegación interna', str_contains($html, 'class="ce-nav"'));
        $activePattern = '/ce-nav__item is-active[^>]+seccion=' . preg_quote((string) $section, '/') . '/';
        report($name . ': sección activa', (bool) preg_match($activePattern, $html), (string) $section);
        report($name . ': CSS del módulo', str_contains($html, 'assets/css/control-escaneres/control-escaneres.css'));
        report($name . ': módulo activo en sidebar', (bool) preg_match('/sidebar__item active[^>]+data-label="Control de Esc/i', $html));
    } else {
        report($name . ': Dashboard activo', (bool) preg_match('/sidebar__item active[^>]+data-label="Dashboard"/', $html));
    }
}

$assets = [
    '/assets/css/importador.css',
    '/assets/css/vascor-design-system.css',
    '/assets/css/control-escaneres/control-escaneres.css',
    '/assets/js/importador.js',
    '/assets/js/operaciones-patio.js',
];

echo "\nAssets estáticos\n";
foreach ($assets as $asset) {
    $response = requestGet($publicBase . $asset);
    report($asset, $response['status'] === 200 && $response['body'] !== '', 'HTTP ' . $response['status']);
}

echo "\nIntegración reversible del App Shell\n";
$controllerSource = file_get_contents(__DIR__ . '/../../app/Controllers/DashboardController.php');
$entrySource = file_get_contents(__DIR__ . '/../../public/index.php');
$controllerSource = is_string($controllerSource) ? $controllerSource : '';
$entrySource = is_string($entrySource) ? $entrySource : '';

report('Modo predeterminado legacy', str_contains($controllerSource, "private const RENDER_MODE = 'legacy';"));
report('importar.php continúa como flujo legacy', str_contains($controllerSource, "require __DIR__ . '/../Views/inventario/importar.php';"));
report('Sin activación del modo mediante parámetros HTTP', !preg_match('/RENDER_MODE[^;]*\$_(?:GET|POST|SESSION|COOKIE)/s', $controllerSource));
report('importar.php no se usa como contenido del App Shell', !preg_match('/contenidoModulo[^\n]*importar\.php/', $controllerSource));
report('Pipeline nuevo contenido en DashboardController', containsAll($controllerSource, ['LegacyRenderBridge', 'RenderAdapter', 'RenderException']));
$renderAssignment = strpos($controllerSource, '$html = $this->renderAppShell();');
$fallbackCall = strpos($controllerSource, '$this->renderLegacy();', (int) $renderAssignment);
$htmlOutput = strpos($controllerSource, 'echo $html;', (int) $renderAssignment);
report('Fallback previo a salida del HTML nuevo', $renderAssignment !== false && $fallbackCall !== false && $htmlOutput !== false && $fallbackCall < $htmlOutput);
report('Despacho público permanece en DashboardController', containsAll($entrySource, ['$controller = new DashboardController();', '$controller->index();']));

echo "\nResumen: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
