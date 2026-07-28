<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$navigationPath = $root . '/config/rail-navigation.php';
$indexPath = $root . '/app/Views/rail/index.php';
$cssPath = $root . '/public/assets/css/rail/rail.css';
$passed = 0;
$failed = 0;

$test = static function (string $name, callable $assertion) use (&$passed, &$failed): void {
    try {
        $result = $assertion() === true;
    } catch (Throwable $exception) {
        $result = false;
        $name .= ' (' . $exception::class . ': ' . $exception->getMessage() . ')';
    }

    $result ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $result ? 'PASS' : 'FAIL', $name);
};

$navigation = require $navigationPath;
$expected = ['ferrocheck', 'consist-rail', 'facturacion', 'inventario', 'evidencias', 'configuracion'];
$render = static function (string $section, string $subsection) use ($navigation, $indexPath): array {
    $railSection = $section;
    $railSubsection = $subsection;
    $railNavigation = $navigation;
    $railBaseUrl = '/Ferrocheck/public';
    ob_start();
    require $indexPath;
    return [
        'content' => (string) ob_get_clean(),
        'navigation' => isset($railModuleNavigation) && is_string($railModuleNavigation) ? $railModuleNavigation : '',
    ];
};

$ferroRender = $render('ferrocheck', 'incidencias');
$fallbackRender = $render('../../invalid', '../../invalid');
$ferroHtml = $ferroRender['content'];
$ferroNavigation = $ferroRender['navigation'];
$fallbackHtml = $fallbackRender['content'];
$viewSources = '';
foreach ($expected as $section) {
    $view = $section === 'ferrocheck' ? 'ferro' : $section;
    $viewSources .= (string) file_get_contents($root . '/app/Views/rail/' . $view . '.php');
}
$railSources = (string) file_get_contents($indexPath)
    . (string) file_get_contents($root . '/app/Views/rail/partials/navigation.php')
    . $viewSources;
$css = (string) file_get_contents($cssPath);

$test('configuración declara las seis secciones en orden', static fn (): bool => array_keys($navigation) === $expected);
$test('cada sección declara metadatos y subsecciones', static function () use ($navigation): bool {
    foreach ($navigation as $key => $section) {
        if (!is_array($section)
            || ($section['key'] ?? null) !== $key
            || trim((string) ($section['label'] ?? '')) === ''
            || trim((string) ($section['description'] ?? '')) === ''
            || trim((string) ($section['icon'] ?? '')) === ''
            || !isset($section['subsections'][$section['default_subsection'] ?? null])
        ) {
            return false;
        }
    }
    return true;
});
$test('badges de estado usan un solo contrato de configuración', static fn (): bool =>
    ($navigation['ferrocheck']['badge'] ?? null) === 'Principal'
    && ($navigation['consist-rail']['badge'] ?? null) === 'En desarrollo'
    && ($navigation['facturacion']['badge'] ?? null) === 'Próximamente'
    && ($navigation['inventario']['badge'] ?? null) === 'Próximamente'
    && ($navigation['evidencias']['badge'] ?? null) === 'Próximamente'
    && !array_key_exists('badge', $navigation['configuracion'])
);
$test('todas las secciones tienen una vista controlada', static function () use ($expected, $root): bool {
    foreach ($expected as $section) {
        $view = $section === 'ferrocheck' ? 'ferro' : $section;
        if (!is_file($root . '/app/Views/rail/' . $view . '.php')) {
            return false;
        }
    }
    return true;
});
$test('render aislado separa dos barras del contenido', static fn (): bool => substr_count($ferroNavigation, '<nav ') === 2 && !str_contains($ferroHtml, '<nav '));
$test('navegación marca sección y subsección activas', static fn (): bool => substr_count($ferroNavigation, 'aria-current="page"') === 2);
$test('navegación renderiza badges con un componente único', static fn (): bool =>
    substr_count($ferroNavigation, 'class="rail-nav-badge"') === 5
    && str_contains($ferroNavigation, '>Principal</span>')
    && str_contains($ferroNavigation, '>En desarrollo</span>')
    && substr_count($ferroNavigation, '>Próximamente</span>') === 3
);
$test('FerroCheck conserva la URL histórica', static fn (): bool => str_contains($ferroNavigation, 'modulo=ferrocheck&amp;seccion=dashboard'));
$test('sección inválida cae en FerroCheck', static fn (): bool => str_contains($fallbackHtml, '<h1 id="railSectionTitle">FerroCheck</h1>'));
$test('vista no duplica documento ni shell global', static fn (): bool => !preg_match('/<!doctype|<html(?:\\s|>)|<head(?:\\s|>)|<body(?:\\s|>)|app-header|app-sidebar|app-footer/i', $railSources));
$test('vistas internas usan encabezado sencillo sin banners repetidos', static function () use ($expected, $root): bool {
    foreach ($expected as $section) {
        $view = $section === 'ferrocheck' ? 'ferro' : $section;
        $source = (string) file_get_contents($root . '/app/Views/rail/' . $view . '.php');
        if (!str_contains($source, 'class="rail-section-heading"') || str_contains($source, 'class="rail-section-header"')) {
            return false;
        }
    }
    return true;
});
$test('vistas no leen superglobales', static fn (): bool => !preg_match('/\\$_(?:GET|POST|SESSION|FILES|COOKIE|SERVER)/', $railSources));
$test('vistas no contienen SQL y el formulario queda aislado en Consist Rail', static function () use ($railSources, $root): bool {
    if (preg_match('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i', $railSources)) {
        return false;
    }
    foreach (['ferro', 'facturacion', 'inventario', 'evidencias', 'configuracion'] as $view) {
        $source = (string) file_get_contents($root . '/app/Views/rail/' . $view . '.php');
        if (preg_match('/<form|<table/i', $source)) {
            return false;
        }
    }
    $consist = (string) file_get_contents($root . '/app/Views/rail/consist-rail.php');
    return substr_count($consist, '<form') === 1 && str_contains($consist, 'enctype="multipart/form-data"');
});
$test('CSS está acotado a Rail y las clases reutilizadas permanecen contextualizadas', static function () use ($css): bool {
    preg_match_all('/\\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $css, $matches);
    $sharedProgressClasses = ['progress-block', 'progress-labels', 'progress-bar', 'progress-bar-fill', 'status-message'];
    foreach ($matches[1] as $className) {
        if (!str_starts_with($className, 'rail-') && !in_array($className, $sharedProgressClasses, true)) {
            return false;
        }
    }
    return $matches[1] !== []
        && !preg_match('/(^|[},])\\s*\\.(?:progress-block|progress-labels|progress-bar|progress-bar-fill|status-message)\\b/m', $css)
        && !preg_match('/(^|[},])\\s*(?::root|html|body|\\*)\\b/m', $css);
});
$test('CSS contiene overflow interno y soporte móvil', static fn (): bool => str_contains($css, 'overflow-x: auto') && str_contains($css, 'min-width: 0') && str_contains($css, '@media (max-width: 640px)') && str_contains($css, 'prefers-reduced-motion'));
$test('JavaScript de Rail queda aislado al progreso de Nuevo Consist', static function () use ($root): bool {
    $directory = $root . '/public/assets/js/rail';
    $files = is_dir($directory) ? array_values(array_diff(scandir($directory) ?: [], ['.', '..'])) : [];
    if ($files !== ['consist-upload-progress.js']) {
        return false;
    }
    $script = (string) file_get_contents($directory . '/consist-upload-progress.js');
    return str_contains($script, '[data-consist-upload-form]')
        && !str_contains($script, 'fetch(')
        && !str_contains($script, 'preventDefault');
});

echo "\nResumen Rail Module Structure: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
