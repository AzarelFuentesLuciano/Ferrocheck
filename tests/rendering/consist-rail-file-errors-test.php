<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\ViewModels\Rail\ConsistUploadViewModel;

$root = dirname(__DIR__, 2);
$configuration = require $root . '/config/consist-rail-import.php';
$render = static function (array $messages) use ($root, $configuration): string {
    $railSubsection = 'registrar';
    $railBaseUrl = '/Ferrocheck/public';
    $railEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $consistUpload = new ConsistUploadViewModel(
        'csrf-token',
        $messages,
        null,
        $configuration,
    );
    ob_start();
    require $root . '/app/Views/rail/consist-rail.php';
    return (string) ob_get_clean();
};
$card = static function (string $html, string $label): string {
    $quoted = preg_quote($label, '/');
    return preg_match('/<label class="rail-consist-file"[^>]*>.*?<span>' . $quoted . '<\/span>(.*?)<\/label>/su', $html, $match) === 1
        ? $match[1]
        : '';
};
$messageFor = static function (string $field, string $expected, string $detected): array {
    return [[
        'type' => 'error',
        'field' => $field,
        'message' => sprintf(
            'No se pudo validar el lote. El archivo cargado en “%s” no corresponde al formato esperado. El sistema detectó que parece ser un archivo “%s”. Seleccione el archivo correcto y vuelva a intentarlo.',
            $expected,
            $detected,
        ),
    ]];
};
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};

$vehicleHtml = $render($messageFor('vehicle_load_report', 'Vehicle Load Report', 'Shippers'));
$test('error Vehicle Load aparece únicamente en su tarjeta',
    str_contains($card($vehicleHtml, 'Vehicle Load Report'), 'El archivo corresponde a Shippers.')
    && !str_contains($card($vehicleHtml, 'Shippers'), 'El archivo corresponde')
    && !str_contains($card($vehicleHtml, 'CNACS'), 'El archivo corresponde')
    && substr_count($vehicleHtml, 'El archivo corresponde a Shippers.') === 1
);
$test('banner global se conserva para Vehicle Load',
    str_contains($vehicleHtml, 'No se pudo validar el lote.')
    && str_contains($vehicleHtml, 'Vehicle Load Report')
    && str_contains($vehicleHtml, 'parece ser un archivo “Shippers”')
);

$shippersHtml = $render($messageFor('shippers', 'Shippers', 'CNACS'));
$test('error Shippers aparece únicamente en su tarjeta',
    !str_contains($card($shippersHtml, 'Vehicle Load Report'), 'El archivo corresponde')
    && str_contains($card($shippersHtml, 'Shippers'), 'El archivo corresponde a CNACS.')
    && !str_contains($card($shippersHtml, 'CNACS'), 'El archivo corresponde')
    && substr_count($shippersHtml, 'El archivo corresponde a CNACS.') === 1
);

$cnacsHtml = $render($messageFor('cnacs', 'CNACS', 'Vehicle Load Report'));
$test('error CNACS aparece únicamente en su tarjeta',
    !str_contains($card($cnacsHtml, 'Vehicle Load Report'), 'El archivo corresponde')
    && !str_contains($card($cnacsHtml, 'Shippers'), 'El archivo corresponde')
    && str_contains($card($cnacsHtml, 'CNACS'), 'El archivo corresponde a Vehicle Load Report.')
    && substr_count($cnacsHtml, 'El archivo corresponde a Vehicle Load Report.') === 1
);

$cleanHtml = $render([]);
$test('sin errores las tres tarjetas permanecen limpias',
    substr_count($cleanHtml, 'data-state="pending"') === 3
    && !str_contains($cleanHtml, 'El archivo corresponde a')
    && substr_count($cleanHtml, 'Pendiente de validación.') === 3
);

echo "\nResumen Consist Rail File Errors: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
