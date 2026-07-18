<?php

declare(strict_types=1);

use App\Core\Rendering\Exceptions\RenderException;
use App\Core\Rendering\RenderAdapter;
use App\Core\Rendering\RenderContext;

require_once __DIR__ . '/../../app/Core/Rendering/Exceptions/RenderException.php';
require_once __DIR__ . '/../../app/Core/Rendering/RenderContext.php';
require_once __DIR__ . '/../../app/Core/Rendering/RenderAdapter.php';

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

$styles = ['/assets/css/module.css', '/assets/css/extra.css', '/assets/css/module.css'];
$scripts = ['/assets/js/module.js', '/assets/js/extra.js', '/assets/js/module.js'];
$trustedContent = '<section id="renderTestContent"><strong>HTML confiable</strong></section>';
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

$context = new RenderContext(
    pageTitle: 'VASCOR OPS | Render Adapter',
    documentLanguage: 'es-MX',
    assetBaseUrl: '/Ferrocheck/public',
    activeModule: 'FerroCheck',
    activeSection: 'Consulta-VIN',
    modules: $modules,
    moduleNavigation: '<nav id="renderTestNavigation">Navegación interna</nav>',
    content: $trustedContent,
    additionalStyles: $styles,
    additionalScripts: $scripts,
    header: ['systemName' => 'VASCOR OPS'],
    footer: ['title' => 'VASCOR OPS'],
    sidebarLabel: 'Módulos principales'
);

$adapter = new RenderAdapter();
ob_start();
$html = $adapter->render($context);
$outsideOutput = ob_get_clean();
$data = $context->toArray();

$test('Construcción válida de RenderContext', static fn (): bool => $data['pageTitle'] === 'VASCOR OPS | Render Adapter');
$test('Renderizado devuelve string no vacío', static fn (): bool => $html !== '');
$test('Existe una sola etiqueta html', static fn (): bool => preg_match_all('/<html\b/i', $html) === 1);
$test('Existe una sola etiqueta head', static fn (): bool => preg_match_all('/<head\b/i', $html) === 1);
$test('Existe una sola etiqueta body', static fn (): bool => preg_match_all('/<body\b/i', $html) === 1);
$test('Existe una sola estructura principal App Shell', static fn (): bool => substr_count($html, 'data-app-shell>') === 1);
$test('El contenido aparece dentro del layout', static fn (): bool => str_contains($html, $trustedContent));
$test('El pageTitle aparece correctamente', static fn (): bool => str_contains($html, '<title>VASCOR OPS | Render Adapter</title>'));
$test('El módulo activo se normaliza y conserva', static fn (): bool => $data['activeModule'] === 'ferrocheck' && str_contains($html, 'app-sidebar-link is-active'));
$test('La sección activa se normaliza y conserva', static fn (): bool => $data['activeSection'] === 'consulta-vin' && str_contains($html, 'app-sidebar-submenu__link is-active'));
$test('Assets duplicados se deduplican', static fn (): bool => substr_count($html, '/assets/css/module.css') === 1 && substr_count($html, '/assets/js/module.js') === 1);
$test('El orden de assets se conserva', static fn (): bool => strpos($html, '/assets/css/module.css') < strpos($html, '/assets/css/extra.css') && strpos($html, '/assets/js/module.js') < strpos($html, '/assets/js/extra.js'));
$test('HTML confiable no se escapa dos veces', static fn (): bool => str_contains($html, '<strong>HTML confiable</strong>') && !str_contains($html, '&lt;strong&gt;HTML confiable'));
$test('Título vacío genera error', static function (): bool {
    try {
        new RenderContext(pageTitle: '   ');
        return false;
    } catch (InvalidArgumentException) {
        return true;
    }
});
$test('Idioma inválido genera error', static function (): bool {
    try {
        new RenderContext(documentLanguage: 'es<script>');
        return false;
    } catch (InvalidArgumentException) {
        return true;
    }
});
$test('Asset inválido genera error', static function (): bool {
    try {
        new RenderContext(additionalScripts: ['javascript:alert(1)']);
        return false;
    } catch (InvalidArgumentException) {
        return true;
    }
});
$test('RenderAdapter no imprime fuera del valor retornado', static fn (): bool => $outsideOutput === '');
$test('Layout ausente provoca RenderException', static function (): bool {
    $missingLayout = __DIR__ . '/../../app/Views/layouts/__missing-render-layout.php';
    try {
        (new RenderAdapter($missingLayout))->render(new RenderContext());
        return false;
    } catch (RenderException) {
        return true;
    }
});

echo "\nResumen Render Adapter: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
