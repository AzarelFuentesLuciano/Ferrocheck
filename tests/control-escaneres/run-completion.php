<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\DTO\ControlEscaneres\{AuthenticatedActorData, ScannerEvidenceMetadata};
use App\Services\ControlEscaneres\Evidence\EvidenceFileStorage;
use App\Services\ControlEscaneres\Qr\ScannerQrCodeService;
use App\Services\ControlEscaneres\Reports\ScannerReportService;

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents(__DIR__ . '/fixtures/sqlite-schema.sql'));
$insert = $pdo->prepare("INSERT INTO scanners(codigo,codigo_qr,tag_original,marca,modelo,estado,activo,indice_conservacion) VALUES(:code,:qr,:tag,'Zebra','TC22','disponible',1,95)");
$insert->execute(['code' => 'SC-7001', 'qr' => 'http://localhost/index.php?modulo=control-escaneres&seccion=expediente&scanner_id=1', 'tag' => '7001']);
$insert->execute(['code' => 'SC-7002', 'qr' => 'http://localhost/index.php?modulo=control-escaneres&seccion=expediente&scanner_id=2', 'tag' => '7002']);

$qr = new ScannerQrCodeService($pdo);
$firstQr = $qr->png(1, 240);
$secondQr = $qr->png(2, 240);
test('QR genera PNG local', fn () => same("\x89PNG", substr($firstQr['bytes'], 0, 4)));
test('QR conserva identidad individual', fn () => ok(hash('sha256', $firstQr['bytes']) !== hash('sha256', $secondQr['bytes'])));
test('QR limita tamaño solicitado', fn () => ok(strlen($qr->png(1, 5000)['bytes']) > 100));
test('QR rechaza equipo inexistente', fn () => throws(fn () => $qr->png(999)));

$reports = new ScannerReportService($pdo);
$report = $reports->generate(['tipo' => 'mensual', 'q' => '7001']);
test('reporte filtra equipo', fn () => same(1, count($report['rows'])));
test('reporte no expone identificadores sensibles', fn () => ok(!array_key_exists('imei', $report['rows'][0]) && !array_key_exists('iccid', $report['rows'][0]) && !array_key_exists('telefono', $report['rows'][0])));
$pdf = $reports->pdf($report);
$xlsx = $reports->xlsx($report);
test('reporte PDF válido', fn () => same('%PDF-', substr($pdf['bytes'], 0, 5)));
test('reporte Excel válido', fn () => same('PK', substr($xlsx['bytes'], 0, 2)));
test('reporte rechaza rango excesivo', fn () => throws(fn () => $reports->generate(['desde' => '2024-01-01', 'hasta' => '2026-01-01'])));

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ce-evidence-' . bin2hex(random_bytes(6));
$storage = new EvidenceFileStorage($root);
$actor = new AuthenticatedActorData(7, 'completion-test');
if (!extension_loaded('gd')) {
    test('GD disponible para prueba de firma', fn () => ok(false));
} else {
    $image = imagecreatetruecolor(80, 30);
    imagefilledrectangle($image, 0, 0, 79, 29, imagecolorallocate($image, 255, 255, 255));
    imageline($image, 5, 20, 70, 8, imagecolorallocate($image, 0, 0, 0));
    ob_start(); imagepng($image); $signatureBytes = (string) ob_get_clean(); imagedestroy($image);
    $metadata = $storage->signature('data:image/png;base64,' . base64_encode($signatureBytes), 1, 'firma_usuario_entrega', $actor);
    $read = $storage->read($metadata);
    test('firma se guarda fuera de base64', fn () => ok($metadata->mimeType === 'image/png' && !str_contains($metadata->storagePath, 'data:')));
    test('firma conserva hash e integridad', fn () => same(hash('sha256', $read['bytes']), $metadata->sha256));
    test('ruta de evidencia es aleatoria', fn () => ok((bool) preg_match('#^\d{4}/\d{2}/[a-f0-9]{40}\.png$#', $metadata->storagePath)));
    $bad = new ScannerEvidenceMetadata(1, 'firma', '../outside.png', 'image/png', 101, str_repeat('a', 64), new DateTimeImmutable(), $actor);
    test('evidencia bloquea path traversal', fn () => throws(fn () => $storage->read($bad)));
    $storage->remove($metadata);
}

finish('Completion Features');
