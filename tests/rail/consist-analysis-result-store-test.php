<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistAnalysisResultStore;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'analysis_store_' . bin2hex(random_bytes(5));
$session = [];
$mover = static fn (string $source, string $target): bool => copy($source, $target);
$temporaryStore = new ConsistTemporaryUploadStore($root, $session, 5, 'session-owner', 1800, $mover);
$sources = [];
$metadata = [];
foreach (['vehicle_load_report','shippers','cnacs'] as $field) {
    $path = tempnam(sys_get_temp_dir(), 'analysis_source_');
    file_put_contents($path, "VIN\n{$field}\n");
    $sources[] = $path;
    $metadata[$field] = ['field'=>$field,'label'=>$field,'tmp_path'=>$path,'original_name'=>$field.'.csv','extension'=>'csv','mime'=>'text/plain','reader_type'=>'Csv','size'=>filesize($path)];
}
$entry = $temporaryStore->stageBatch($metadata);
$token = $entry['token'];
$store = new ConsistAnalysisResultStore($temporaryStore, 5, 'session-owner');
$result = ['consistency'=>['total_unique_combined'=>3], 'categories'=>[]];
$store->save($token, $result);
$passed = 0;
$failed = 0;
$test = static function (string $label, bool $ok) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$test('guarda y recupera resultado fuera de sesión', $store->load($token) === $result && !str_contains(serialize($session), 'total_unique_combined'));
$foreignSession = $session;
$foreignTemporary = new ConsistTemporaryUploadStore($root, $foreignSession, 6, 'session-other', 1800, $mover);
$foreignStore = new ConsistAnalysisResultStore($foreignTemporary, 6, 'session-other');
try { $foreignStore->load($token); $foreignRejected = false; } catch (RuntimeException) { $foreignRejected = true; }
$test('rechaza usuario y sesión diferentes', $foreignRejected);
try { $store->load('../invalid'); $invalidRejected = false; } catch (RuntimeException) { $invalidRejected = true; }
$test('rechaza token inválido', $invalidRejected);
$analysisPath = dirname($entry['files']['cnacs']['path']) . DIRECTORY_SEPARATOR . 'vin-analysis.json';
file_put_contents($analysisPath, '{"payload":{},"signature":"alterada"}');
try { $store->load($token); $tamperRejected = false; } catch (RuntimeException) { $tamperRejected = true; }
$test('rechaza resultado alterado', $tamperRejected);
$store->save($token, $result);
file_put_contents($entry['files']['cnacs']['path'], 'alterado');
try { $store->load($token); $hashRejected = false; } catch (RuntimeException) { $hashRejected = true; }
$test('rechaza archivo del lote alterado', $hashRejected);

$temporaryStore->discard($token);
$expiredEntry = $temporaryStore->stageBatch($metadata);
$session['_consist_rail_uploads'][$expiredEntry['token']]['expires_at'] = time() - 1;
try { $temporaryStore->resolve($expiredEntry['token']); $expiredRejected = false; } catch (RuntimeException) { $expiredRejected = true; }
$test('rechaza lote expirado', $expiredRejected);

$missingEntry = $temporaryStore->stageBatch($metadata);
unlink($missingEntry['files']['cnacs']['path']);
try { $temporaryStore->resolve($missingEntry['token']); $missingRejected = false; } catch (RuntimeException) { $missingRejected = true; }
$test('rechaza archivo faltante', $missingRejected);
$temporaryStore->discard($missingEntry['token']);

$outsideEntry = $temporaryStore->stageBatch($metadata);
$outside = tempnam(sys_get_temp_dir(), 'analysis_outside_');
file_put_contents($outside, 'fuera');
$session['_consist_rail_uploads'][$outsideEntry['token']]['files']['cnacs']['path'] = $outside;
$session['_consist_rail_uploads'][$outsideEntry['token']]['files']['cnacs']['sha256'] = hash_file('sha256', $outside);
try { $temporaryStore->resolve($outsideEntry['token']); $outsideRejected = false; } catch (RuntimeException) { $outsideRejected = true; }
$test('rechaza ruta fuera del directorio del lote', $outsideRejected);
$temporaryStore->discard($outsideEntry['token']);
unlink($outside);

foreach ($sources as $path) { if (is_file($path)) unlink($path); }
if (is_dir($root)) rmdir($root);
echo "\nResumen Consist Analysis Result Store: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
