<?php

declare(strict_types=1);

use App\Core\Rendering\Exceptions\RenderException;
use App\Core\Rendering\LegacyRenderBridge;
use App\Core\Rendering\RenderAdapter;
use App\Core\Rendering\RenderContext;

require_once __DIR__ . '/../../app/Core/Rendering/Exceptions/RenderException.php';
require_once __DIR__ . '/../../app/Core/Rendering/RenderContext.php';
require_once __DIR__ . '/../../app/Core/Rendering/RenderAdapter.php';
require_once __DIR__ . '/../../app/Core/Rendering/LegacyRenderBridge.php';

$passed = 0;
$failed = 0;

$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try {
        $ok = $assertion() === true;
    } catch (Throwable $exception) {
        $ok = false;
        $label .= ' (' . $exception::class . ')';
    }

    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};

$bridge = new LegacyRenderBridge();
$trustedContent = '<section id="legacyContent"><strong>Contenido legacy</strong></section>';
$trustedNavigation = '<nav id="legacyNavigation">Sección legacy</nav>';
$modules = [[
    'id' => 'ferrocheck',
    'label' => 'FerroCheck',
    'url' => '/index.php?modulo=ferrocheck',
    'icon' => 'F',
    'sections' => [[
        'id' => 'consulta-vin',
        'label' => 'Consulta VIN',
        'url' => '/index.php?modulo=ferrocheck&seccion=consulta-vin',
    ]],
]];
$legacy = [
    'tituloPagina' => 'Título legacy',
    'pageTitle' => 'VASCOR OPS | Bridge',
    'documentLanguage' => 'es-MX',
    'BASE_URL' => '/legacy-base',
    'baseUrl' => '/Ferrocheck/public',
    'modulo' => 'FerroCheck',
    'seccion' => 'Consulta-VIN',
    'vistaActual' => '../../archivo-que-no-debe-ejecutarse.php',
    'contenidoModulo' => $trustedContent,
    'modules' => $modules,
    'moduleNavigation' => $trustedNavigation,
    'additionalStyles' => ['/assets/css/legacy.css', '/assets/css/extra.css', '/assets/css/legacy.css'],
    'additionalScripts' => ['/assets/js/legacy.js', '/assets/js/extra.js', '/assets/js/legacy.js'],
    'header' => ['systemName' => 'VASCOR OPS Bridge'],
    'footer' => ['title' => 'VASCOR OPS Bridge'],
    'sidebarLabel' => 'Navegación legacy',
];

ob_start();
$context = $bridge->createContext($legacy);
$bridgeOutput = ob_get_clean();
$data = $context->toArray();
$defaults = $bridge->createContext([])->toArray();

$test('Crea un RenderContext válido', static fn (): bool => $context instanceof RenderContext);
$test('Usa defaults cuando faltan opcionales', static fn (): bool => $defaults['pageTitle'] === 'VASCOR OPS' && $defaults['activeModule'] === 'dashboard' && $defaults['modules'] === []);
$test('Mapea tituloPagina a pageTitle', static fn (): bool => $bridge->createContext(['tituloPagina' => '  Título mapeado  '])->toArray()['pageTitle'] === 'Título mapeado');
$test('pageTitle tiene prioridad sobre tituloPagina', static fn (): bool => $data['pageTitle'] === 'VASCOR OPS | Bridge');
$test('Mapea BASE_URL a assetBaseUrl', static fn (): bool => $bridge->createContext(['BASE_URL' => '/base-alias'])->toArray()['assetBaseUrl'] === '/base-alias');
$test('baseUrl tiene prioridad sobre BASE_URL', static fn (): bool => $data['assetBaseUrl'] === '/Ferrocheck/public');
$test('Mapea modulo a activeModule', static fn (): bool => $data['activeModule'] === 'ferrocheck');
$test('Mapea seccion a activeSection', static fn (): bool => $data['activeSection'] === 'consulta-vin');
$test('Mapea contenidoModulo a content', static fn (): bool => $data['content'] === $trustedContent);
$test('Conserva HTML interno sin doble escape', static fn (): bool => str_contains($data['content'], '<strong>Contenido legacy</strong>'));
$test('Conserva moduleNavigation como HTML string', static fn (): bool => $data['moduleNavigation'] === $trustedNavigation);
$test('Conserva modules y su orden', static fn (): bool => $data['modules'] === $modules);
$test('Conserva header y footer', static fn (): bool => $data['header']['systemName'] === 'VASCOR OPS Bridge' && $data['footer']['title'] === 'VASCOR OPS Bridge');
$test('Conserva sidebarLabel', static fn (): bool => $data['sidebarLabel'] === 'Navegación legacy');
$test('Pasa assets al RenderContext', static fn (): bool => $data['additionalStyles'][0] === '/assets/css/legacy.css' && $data['additionalScripts'][0] === '/assets/js/legacy.js');
$test('RenderContext deduplica assets y conserva orden', static fn (): bool => $data['additionalStyles'] === ['/assets/css/legacy.css', '/assets/css/extra.css'] && $data['additionalScripts'] === ['/assets/js/legacy.js', '/assets/js/extra.js']);
$test('vistaActual no forma parte del contexto', static fn (): bool => !array_key_exists('vistaActual', $data));
$test('vistaActual no se ejecuta', static function () use ($bridge): bool {
    $executed = false;
    $bridge->createContext(['vistaActual' => static function () use (&$executed): void { $executed = true; }]);
    return $executed === false;
});
$test('El bridge no imprime salida', static fn (): bool => $bridgeOutput === '');
$test('Título inválido produce RenderException', static function () use ($bridge): bool {
    try {
        $bridge->createContext(['pageTitle' => '   ']);
        return false;
    } catch (RenderException $exception) {
        return $exception->getPrevious() instanceof InvalidArgumentException;
    }
});
$test('Tipo inválido en modules produce RenderException', static function () use ($bridge): bool {
    try { $bridge->createContext(['modules' => 'invalid']); return false; } catch (RenderException) { return true; }
});
$test('Tipo inválido en assets produce RenderException', static function () use ($bridge): bool {
    try { $bridge->createContext(['additionalStyles' => 'invalid']); return false; } catch (RenderException) { return true; }
});
$test('Tipo inválido dentro de assets produce RenderException', static function () use ($bridge): bool {
    try { $bridge->createContext(['additionalScripts' => [new stdClass()]]); return false; } catch (RenderException $exception) { return $exception->getPrevious() instanceof InvalidArgumentException; }
});
$test('Tipo inválido en contenidoModulo produce RenderException', static function () use ($bridge): bool {
    try { $bridge->createContext(['contenidoModulo' => []]); return false; } catch (RenderException) { return true; }
});
$test('No acepta superglobales completas', static function () use ($bridge): bool {
    try { $bridge->createContext(['_GET' => ['modulo' => 'ferrocheck']]); return false; } catch (RenderException) { return true; }
});

ob_start();
$integratedHtml = (new RenderAdapter())->render($context);
$integratedOutsideOutput = ob_get_clean();

$test('Integración aislada devuelve HTML no vacío', static fn (): bool => $integratedHtml !== '');
$test('Integración aislada contiene una etiqueta html', static fn (): bool => preg_match_all('/<html\b/i', $integratedHtml) === 1);
$test('Integración aislada contiene una etiqueta head', static fn (): bool => preg_match_all('/<head\b/i', $integratedHtml) === 1);
$test('Integración aislada contiene una etiqueta body', static fn (): bool => preg_match_all('/<body\b/i', $integratedHtml) === 1);
$test('Integración aislada conserva contenido y título', static fn (): bool => str_contains($integratedHtml, $trustedContent) && str_contains($integratedHtml, '<title>VASCOR OPS | Bridge</title>'));
$test('Integración aislada no imprime fuera del retorno', static fn (): bool => $integratedOutsideOutput === '');

echo "\nResumen Legacy Render Bridge: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
