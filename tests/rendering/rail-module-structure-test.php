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
$test('FerroCheck conserva la URL histórica', static fn (): bool => str_contains($ferroNavigation, 'modulo=ferrocheck&amp;seccion=dashboard'));
$test('sección inválida cae en FerroCheck', static fn (): bool => str_contains($fallbackHtml, '<h1 id="railSectionTitle">FerroCheck</h1>'));
$test('vista no duplica documento ni shell global', static fn (): bool => !preg_match('/<!doctype|<html(?:\\s|>)|<head(?:\\s|>)|<body(?:\\s|>)|app-header|app-sidebar|app-footer/i', $railSources));
$test('vistas no leen superglobales', static fn (): bool => !preg_match('/\\$_(?:GET|POST|SESSION|FILES|COOKIE|SERVER)/', $railSources));
$test('vistas no contienen SQL ni lógica funcional', static fn (): bool => !preg_match('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b|<form|<table/i', $railSources));
$test('CSS está acotado al prefijo Rail', static function () use ($css): bool {
    preg_match_all('/\\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $css, $matches);
    foreach ($matches[1] as $className) {
        if (!str_starts_with($className, 'rail-')) {
            return false;
        }
    }
    return $matches[1] !== []
        && !preg_match('/(^|[},])\\s*(?::root|html|body|\\*)\\b/m', $css);
});
$test('CSS contiene overflow interno y soporte móvil', static fn (): bool => str_contains($css, 'overflow-x: auto') && str_contains($css, 'min-width: 0') && str_contains($css, '@media (max-width: 640px)') && str_contains($css, 'prefers-reduced-motion'));
$test('módulo no agrega JavaScript', static fn (): bool => !is_dir($root . '/public/assets/js/rail'));

echo "\nResumen Rail Module Structure: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
