<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistFileTypeDetector;

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$detector = new ConsistFileTypeDetector($configuration);
$passed = 0;
$failed = 0;
$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try { $ok = $assertion() === true; } catch (Throwable $e) { $ok = false; $label .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$vehicle = ['fdWholeVIN', 'fdTransportationName1', 'fdLoadId', 'fdTrack', 'fdDestinationLocation', 'fdShipperNumberPrefix'];
$shippers = ['fdWholeVIN1', 'fdPedimentoType', 'fdPortCode', 'fdBillNumber', 'fdBillDate', 'fdPrintDate'];
$cnacs = ['VIN', 'InvoiceNo', 'NumRemesa', 'BrokerId', 'H/SCODE', 'BodyModel', 'Conveyance'];

$test('Vehicle Load correcto en Vehicle Load', static fn (): bool => $detector->detect('vehicle_load_report', $vehicle)->isValid);
$test('Shippers correcto en Shippers', static fn (): bool => $detector->detect('shippers', $shippers)->isValid);
$test('CNACS correcto en CNACS', static fn (): bool => $detector->detect('cnacs', $cnacs)->isValid);
$test('CNACS en Shippers se rechaza y detecta como CNACS', static function () use ($detector, $cnacs): bool {
    $result = $detector->detect('shippers', $cnacs);
    return !$result->isValid && $result->detectedType === 'cnacs' && $result->matches['cnacs'] === 6;
});
$test('Shippers en Vehicle Load se rechaza y detecta como Shippers', static function () use ($detector, $shippers): bool {
    $result = $detector->detect('vehicle_load_report', $shippers);
    return !$result->isValid && $result->detectedType === 'shippers';
});
$test('Vehicle Load en CNACS se rechaza', static fn (): bool => !$detector->detect('cnacs', $vehicle)->isValid);
$test('VIN sin firma suficiente es estructura desconocida', static function () use ($detector): bool {
    $result = $detector->detect('cnacs', ['VIN', 'InvoiceNo', 'Dato']);
    return !$result->isValid && $result->isUnknown && $result->detectedType === null;
});
$test('empate de firmas se rechaza como ambiguo', static function () use ($detector): bool {
    $headers = ['VIN', 'fdLoadId', 'fdTrack', 'fdDestinationLocation', 'InvoiceNo', 'NumRemesa', 'BrokerId'];
    $result = $detector->detect('cnacs', $headers);
    return !$result->isValid && $result->isAmbiguous && $result->detectedType === null;
});
$test('encabezados con mayúsculas y minúsculas se aceptan', static fn (): bool =>
    $detector->detect('shippers', array_map('strtoupper', $shippers))->isValid
);
$test('encabezados con espacios alrededor se aceptan', static fn (): bool =>
    $detector->detect('cnacs', array_map(static fn (string $header): string => "  {$header}  ", $cnacs))->isValid
);
$test('resultado estructurado conserva puntuaciones y faltantes', static function () use ($detector, $vehicle): bool {
    $result = $detector->detect('vehicle_load_report', $vehicle)->toArray();
    return $result['expected_type'] === 'vehicle_load_report'
        && $result['matches']['vehicle_load_report'] === 5
        && $result['missing_identity_headers'] === [];
});

echo "\nResumen Consist File Type Detector: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
