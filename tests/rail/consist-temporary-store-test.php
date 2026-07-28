<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistTemporaryUploadStore;

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'consist_store_' . bin2hex(random_bytes(5));
$session = [];
$mover = static fn (string $source, string $target): bool => copy($source, $target);
$store = new ConsistTemporaryUploadStore($root, $session, 7, 'session-a', 1800, $mover);
$source = tempnam(sys_get_temp_dir(), 'crs_');
file_put_contents($source, "VIN\nABC\n");
$meta = ['field'=>'cnacs','label'=>'CNACS','tmp_path'=>$source,'original_name'=>'cnacs.csv','extension'=>'csv','mime'=>'text/plain','reader_type'=>'Csv','size'=>filesize($source)];
$entry = $store->stageBatch(['cnacs' => $meta]);
$passed = 0;
$failed = 0;
$test = static function (string $name, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $name);
};
$test('genera token de 256 bits', preg_match('/^[a-f0-9]{64}$/', $entry['token']) === 1);
$test('usa nombre interno aleatorio', $entry['files']['cnacs']['stored_name'] !== 'cnacs.csv');
$test('registra SHA-256', strlen($entry['files']['cnacs']['sha256']) === 64);
$test('resuelve únicamente para propietario y sesión', $store->resolve($entry['token'])['user_id'] === 7);
$foreignSession = $session;
$foreign = new ConsistTemporaryUploadStore($root, $foreignSession, 8, 'session-b', 1800, $mover);
try { $foreign->resolve($entry['token']); $isolated = false; } catch (RuntimeException) { $isolated = true; }
$test('rechaza otro usuario o sesión', $isolated);
$store->discard($entry['token']);
$test('descarta físicamente el lote', !is_dir($root . DIRECTORY_SEPARATOR . $entry['token']));
if (is_file($source)) { unlink($source); }
if (is_dir($root)) { rmdir($root); }
echo "\nResumen Consist Temporary Store: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
