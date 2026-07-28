<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistUploadValidator;

$configuration = require dirname(__DIR__, 2) . '/config/consist-rail-import.php';
$passed = 0;
$failed = 0;
$test = static function (string $name, callable $callback) use (&$passed, &$failed): void {
    try { $ok = $callback() === true; } catch (Throwable $e) { $ok = false; $name .= ': ' . $e->getMessage(); }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $name);
};
$makeCsv = static function (string $content): string {
    $path = tempnam(sys_get_temp_dir(), 'crv_');
    file_put_contents($path, $content);
    return $path;
};
$upload = static function (string $path, string $name): array {
    return ['name' => $name, 'tmp_name' => $path, 'size' => filesize($path), 'error' => UPLOAD_ERR_OK];
};
$validator = new ConsistUploadValidator($configuration, static fn (string $path): bool => is_file($path));
$paths = [$makeCsv("VIN\nA1\n"), $makeCsv("VIN\nB1\n"), $makeCsv("VIN\nC1\n")];
$files = [
    'vehicle_load_report' => $upload($paths[0], 'vehicle.csv'),
    'shippers' => $upload($paths[1], 'shippers.csv'),
    'cnacs' => $upload($paths[2], 'cnacs.csv'),
];

$test('acepta el lote completo CSV', static fn (): bool => count($validator->validateBatch($files)) === 3);
$test('rechaza un archivo obligatorio ausente', static function () use ($validator, $files): bool {
    unset($files['cnacs']);
    try { $validator->validateBatch($files); } catch (RuntimeException) { return true; }
    return false;
});
$test('rechaza extensión distinta al contenido', static function () use ($validator, $files): bool {
    $files['cnacs']['name'] = 'cnacs.xlsx';
    try { $validator->validateBatch($files); } catch (RuntimeException) { return true; }
    return false;
});
foreach ($paths as $path) { unlink($path); }
echo "\nResumen Consist Upload Validator: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
