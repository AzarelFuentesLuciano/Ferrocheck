<?php
declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use App\Domain\ControlEscaneres\{BatteryPercentage, IncidentSeverity, ScannerCode, ScannerFolio, ScannerStatus};
use App\DTO\ControlEscaneres\{AuthenticatedActorData, BusinessRequestContext, IncidentResolutionData, MaintenanceCommandData, ScannerCreateData, ScannerIncidentCreateData, ScannerInspectionDetailData, ScannerMovementCreateData, ScannerReceptionData};
use App\Repositories\ControlEscaneres\Pdo\{PdoAuditRepository, PdoEvidenceRepository, PdoScannerIncidentRepository, PdoScannerInspectionRepository, PdoScannerMovementRepository, PdoScannerRepository};
use App\Repositories\ControlEscaneres\TransactionManager;
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Entrega\ScannerDeliveryService;
use App\Services\ControlEscaneres\Incidencias\ScannerIncidentService;
use App\Services\ControlEscaneres\Mantenimiento\ScannerMaintenanceService;
use App\Services\ControlEscaneres\Recepcion\ScannerReceptionService;
use App\Services\ControlEscaneres\Shared\{BusinessClockInterface, InspectionComparisonService, ScannerStateMachine, UuidOperationalFolioGenerator};
use App\Services\ControlEscaneres\Validaciones\{IncidentPolicy, MaintenancePolicy, MovementPolicy, ScannerAvailabilityPolicy};

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__.'/fixtures/sqlite-schema.sql'));
$now = new DateTimeImmutable('2026-07-18 10:00:00');
$clock = new class($now) implements BusinessClockInterface { public function __construct(private DateTimeImmutable $now) {} public function now(): DateTimeImmutable { return $this->now; } };
$actor = new AuthenticatedActorData(7, 'session', '127.0.0.1');
$context = new BusinessRequestContext('REQ-SVC-1', '127.0.0.1', 'session', 'tests');
$scanners = new PdoScannerRepository($pdo);
$movements = new PdoScannerMovementRepository($pdo);
$inspections = new PdoScannerInspectionRepository($pdo);
$incidents = new PdoScannerIncidentRepository($pdo);
$evidence = new PdoEvidenceRepository($pdo);
$transactions = new TransactionManager($pdo);
$stateMachine = new ScannerStateMachine();
$availability = new ScannerAvailabilityPolicy();
$movementPolicy = new MovementPolicy();
$audit = new ScannerAuditService(new PdoAuditRepository($pdo), $clock);
$deliveryService = new ScannerDeliveryService($scanners, $movements, $inspections, $evidence, $transactions, $stateMachine, new UuidOperationalFolioGenerator($clock), $audit, $availability, $movementPolicy);
$receptionService = new ScannerReceptionService($scanners, $movements, $inspections, $evidence, $transactions, $stateMachine, new InspectionComparisonService(), $clock, $audit, $availability, $movementPolicy);
$incidentService = new ScannerIncidentService($scanners, $incidents, $evidence, $transactions, $stateMachine, $clock, $audit, $availability, new IncidentPolicy());
$maintenanceService = new ScannerMaintenanceService($scanners, $movements, $evidence, $transactions, $stateMachine, $audit, $availability, $movementPolicy, new MaintenancePolicy());

$scanner = $scanners->create(new ScannerCreateData(new ScannerCode('SC-1001'), 'Q1', 'Zebra', 'TC22', new ScannerStatus('disponible')), 7);
$delivery = $deliveryService->deliver(new ScannerMovementCreateData($scanner->id, new ScannerFolio('MOV-20260718-LEGACY'), 'Operador', 'E-1', 'A', new DateTimeImmutable('2026-07-18 08:00:00'), $actor, new BatteryPercentage(90), 100, 'OK', [new ScannerInspectionDetailData('pantalla', 'excelente')]), $actor, $context);
test('entrega cambia estado', fn() => same('entregado', $delivery->scanner->status->value));
test('folio operativo generado', fn() => ok((bool) preg_match('/^MOV-20260718-[A-F0-9]{16}$/', $delivery->movement->folio->value)));
test('entrega crea inspeccion', fn() => same(1, count($inspections->listDetailsByInspectionId($delivery->inspection->id))));

$reception = $receptionService->receive(new ScannerReceptionData($delivery->movement->id, $scanner->id, 'Responsable', new BatteryPercentage(70), 2, 'Pantalla danada', [new ScannerInspectionDetailData('pantalla', 'dañado')], effectiveAt: $now), $actor, $context);
test('recepcion calcula duracion', fn() => same(7200, $reception->durationSeconds));
test('recepcion detecta dano', fn() => same('pendiente_reparacion', $reception->resultingStatus->value));
test('recepcion cierra movimiento', fn() => same('devuelto', $reception->movement->status->value));

$scanners->changeStatus($scanner->id, new ScannerStatus('disponible'), 7);
$reported = $incidentService->report(new ScannerIncidentCreateData($scanner->id, 'daño físico', new IncidentSeverity('alta'), 'Golpe', $now, $actor, $delivery->movement->id), $actor, $context);
test('incidencia critica cambia estado', fn() => same('pendiente_reparacion', $reported->scannerStatus->value));
test('servicio conserva movimiento de incidencia', fn() => same($delivery->movement->id, $reported->incident->movementId));
$resolved = $incidentService->resolve(new IncidentResolutionData($reported->incident->id, 'Reparado', new ScannerStatus('disponible')), $actor, $context);
test('incidencia resuelta', fn() => same('resuelta', $resolved->incident->status->value));
test('incidencia no resuelve dos veces', fn() => throws(fn() => $incidentService->resolve(new IncidentResolutionData($reported->incident->id, 'Otra', new ScannerStatus('disponible')), $actor, $context)));

$sent = $maintenanceService->execute(new MaintenanceCommandData($scanner->id, 'send', 'Preventivo'), $actor, $context);
test('envio a mantenimiento', fn() => same('mantenimiento', $sent->newStatus->value));
$returned = $maintenanceService->execute(new MaintenanceCommandData($scanner->id, 'return', 'Concluido', resultingStatus: new ScannerStatus('disponible')), $actor, $context);
test('retorno de mantenimiento', fn() => same('disponible', $returned->newStatus->value));

$rollbackScanner = $scanners->create(new ScannerCreateData(new ScannerCode('SC-1002'), 'Q2', 'Zebra', 'TC22', new ScannerStatus('disponible')), 7);
test('rollback atomico de entrega', function () use ($deliveryService, $rollbackScanner, $actor, $context, $movements, $scanners): void {
    throws(fn() => $deliveryService->deliver(new ScannerMovementCreateData($rollbackScanner->id, new ScannerFolio('MOV-20260718-ROLLBACK'), 'Operador', 'E-2', 'B', new DateTimeImmutable('2026-07-18 09:00:00'), $actor, evidenceReferences: ['invalida']), $actor, $context));
    same(0, count($movements->listByScannerId($rollbackScanner->id)));
    same('disponible', $scanners->findById($rollbackScanner->id)->status->value);
});
test('auditoria operativa registrada', fn() => ok((int) $pdo->query('SELECT COUNT(*) FROM auditoria_eventos')->fetchColumn() >= 6));

finish('Operational Services');
