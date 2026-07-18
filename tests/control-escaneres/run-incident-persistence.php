<?php
declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use App\Domain\ControlEscaneres\{IncidentSeverity, ScannerCode, ScannerFolio, ScannerStatus};
use App\DTO\ControlEscaneres\{AuthenticatedActorData, ScannerCreateData, ScannerIncidentCreateData, ScannerMovementCreateData};
use App\Repositories\ControlEscaneres\Pdo\{PdoScannerIncidentRepository, PdoScannerMovementRepository, PdoScannerRepository};
use App\Repositories\ControlEscaneres\TransactionManager;

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__.'/fixtures/sqlite-schema.sql'));
$actor = new AuthenticatedActorData(7, 'test-session', '127.0.0.1');
$scanners = new PdoScannerRepository($pdo);
$movements = new PdoScannerMovementRepository($pdo);
$incidents = new PdoScannerIncidentRepository($pdo);
$transactions = new TransactionManager($pdo);
$scanner = $scanners->create(new ScannerCreateData(new ScannerCode('SC-2001'), 'QR-2001', 'Zebra', 'TC22', new ScannerStatus('disponible')), 7);
$movement = $movements->create(new ScannerMovementCreateData($scanner->id, new ScannerFolio('MOV-20260718-INC001'), 'Persona', 'E-2001', 'A', new DateTimeImmutable('2026-07-18 08:00:00'), $actor));
$reportedAt = new DateTimeImmutable('2026-07-18 09:00:00');

$linked = $incidents->create(new ScannerIncidentCreateData($scanner->id, 'daño', new IncidentSeverity('media'), 'Pantalla', $reportedAt, $actor, $movement->id));
test('crear incidencia persiste movimiento_id', fn() => same($movement->id, (int) $pdo->query('SELECT movimiento_id FROM scanner_incidencias WHERE id='.(int) $linked->id)->fetchColumn()));
test('findById conserva movementId', fn() => same($movement->id, $incidents->findById($linked->id)->movementId));
test('listByScannerId conserva movementId', fn() => same($movement->id, $incidents->listByScannerId($scanner->id)[0]->movementId));
test('listByMovementId recupera incidencia', fn() => same($linked->id, $incidents->listByMovementId($movement->id)[0]->id));
test('listByDateRange conserva movementId', fn() => same($movement->id, $incidents->listByDateRange(new DateTimeImmutable('2026-07-18 08:59:00'), new DateTimeImmutable('2026-07-18 09:01:00'))[0]->movementId));

$unlinked = $incidents->create(new ScannerIncidentCreateData($scanner->id, 'observacion', new IncidentSeverity('baja'), 'Sin movimiento', new DateTimeImmutable('2026-07-18 10:00:00'), $actor));
test('incidencia sin movimiento persiste NULL', fn() => same(null, $incidents->findById($unlinked->id)->movementId));

$incidents->changeSeverity($linked->id, new IncidentSeverity('alta'), 7);
test('cambio de severidad conserva movementId', fn() => same($movement->id, $incidents->findById($linked->id)->movementId));
$incidents->resolve($linked->id, 'Reparada', 7, new DateTimeImmutable('2026-07-18 11:00:00'));
test('resolucion conserva movementId', fn() => same($movement->id, $incidents->findById($linked->id)->movementId));

test('rollback no deja incidencia parcial', function () use ($transactions, $incidents, $scanner, $movement, $actor, $pdo): void {
    $before = (int) $pdo->query('SELECT COUNT(*) FROM scanner_incidencias')->fetchColumn();
    throws(fn() => $transactions->transactional(function () use ($incidents, $scanner, $movement, $actor): void {
        $incidents->create(new ScannerIncidentCreateData($scanner->id, 'rollback', new IncidentSeverity('baja'), 'Temporal', new DateTimeImmutable('2026-07-18 12:00:00'), $actor, $movement->id));
        throw new RuntimeException('rollback esperado');
    }));
    same($before, (int) $pdo->query('SELECT COUNT(*) FROM scanner_incidencias')->fetchColumn());
});

finish('Incident Persistence');
