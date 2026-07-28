<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$view = (string) file_get_contents($root . '/app/Views/rail/consist-rail.php');
$css = (string) file_get_contents($root . '/public/assets/css/rail/rail.css');
$script = (string) file_get_contents($root . '/public/assets/js/rail/consist-upload-progress.js');
$controller = (string) file_get_contents($root . '/app/Controllers/Rail/RailController.php');
$ferroView = (string) file_get_contents($root . '/app/Views/inventario/partials/ferrocheck-content.php');
$ferroCss = (string) file_get_contents($root . '/public/assets/css/importador.css');
$ferroJs = (string) file_get_contents($root . '/public/assets/js/importador.js');
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $result) use (&$passed, &$failed): void {
    $result ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $result ? 'PASS' : 'FAIL', $label);
};

$test('Consist reutiliza el contrato visual de progreso de FerroCheck',
    str_contains($ferroView, 'class="progress-block"')
    && str_contains($view, 'class="progress-block"')
    && str_contains($ferroCss, '.progress-bar-fill')
    && str_contains($css, '.rail-consist-file .progress-bar-fill')
);
$test('renderiza una barra accesible por cada selector',
    str_contains($view, 'role="progressbar"')
    && str_contains($view, 'aria-valuemin="0"')
    && str_contains($view, 'aria-valuemax="100"')
    && str_contains($view, 'aria-valuenow=')
    && str_contains($view, 'aria-live="polite"')
);
$test('estados exigidos están implementados',
    preg_match_all('/\\b(?:pending|selected|received|validating|reading|analyzing|valid|error)\\b/', $script) >= 8
    && str_contains($script, 'Analizando VIN, filas vacías y duplicados...')
);
$test('selección termina en 10 y espera del servidor no supera 80',
    str_contains($script, "update(card, 'selected', 10)")
    && str_contains($script, "['analyzing', 80") === false
    && str_contains($script, "'analyzing', 80")
    && !str_contains($script, "update(card, 'valid', 100)")
);
$test('100 por ciento depende del resultado del backend',
    str_contains($view, "\$progressState === 'valid' ? 100 : 0")
    && str_contains($view, 'Archivo validado correctamente.')
);
$test('errores finales muestran texto específico conservado por backend',
    str_contains($view, "\$fileResult['errors'][0]")
    && str_contains($view, '$backendError')
);
$test('JavaScript es mejora progresiva y no intercepta POST',
    !str_contains($script, 'preventDefault')
    && !str_contains($script, 'fetch(')
    && str_contains($view, 'method="post"')
);
$test('script se carga únicamente en Nuevo Consist',
    str_contains($controller, "\$section === 'consist-rail' && \$subsection === 'registrar'")
    && str_contains($controller, '/assets/js/rail/consist-upload-progress.js')
);
$test('archivos originales de FerroCheck conservan su componente',
    str_contains($ferroJs, 'progressFill')
    && str_contains($ferroView, 'id="progressPercent"')
    && str_contains($ferroCss, 'transition: width 0.3s ease')
);

echo "\nResumen Consist Rail Progress: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
