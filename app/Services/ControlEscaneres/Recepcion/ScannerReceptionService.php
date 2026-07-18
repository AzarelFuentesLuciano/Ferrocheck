<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Recepcion;

use App\Domain\ControlEscaneres\{InspectionType, ScannerStatus};
use App\DTO\ControlEscaneres\{AuthenticatedActorData, BusinessRequestContext, ReceptionResult, ScannerEvidenceMetadata, ScannerInspectionCreateData, ScannerReceptionData};
use App\Exceptions\ControlEscaneres\{OpenMovementNotFoundException, ScannerNotFoundException};
use App\Repositories\ControlEscaneres\Contracts\{EvidenceRepositoryInterface, ScannerInspectionRepositoryInterface, ScannerMovementRepositoryInterface, ScannerRepositoryInterface, TransactionManagerInterface};
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Shared\{BusinessClockInterface, InspectionComparisonServiceInterface, ScannerStateMachineInterface};
use App\Services\ControlEscaneres\Validaciones\{MovementPolicy, ScannerAvailabilityPolicy};

final class ScannerReceptionService
{
    public function __construct(
        private ScannerRepositoryInterface $scanners,
        private ScannerMovementRepositoryInterface $movements,
        private ScannerInspectionRepositoryInterface $inspections,
        private EvidenceRepositoryInterface $evidence,
        private TransactionManagerInterface $transactions,
        private ScannerStateMachineInterface $stateMachine,
        private InspectionComparisonServiceInterface $comparison,
        private BusinessClockInterface $clock,
        private ScannerAuditService $audit,
        private ScannerAvailabilityPolicy $availability,
        private MovementPolicy $movementPolicy,
    ) {}

    public function receive(ScannerReceptionData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): ReceptionResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): ReceptionResult {
            $scanner = $this->scanners->lockScannerForUpdate($command->scannerId);
            if ($scanner === null) {
                throw new ScannerNotFoundException('Scanner no encontrado.');
            }
            $this->availability->assertActive($scanner);
            $movement = $this->movements->lockOpenMovementForUpdate($scanner->id);
            if ($movement === null || $movement->id !== $command->movementId) {
                throw new OpenMovementNotFoundException('Movimiento abierto no encontrado.');
            }
            $this->movementPolicy->assertBelongs($movement, $scanner);
            $receivedAt = $command->effectiveAt ?? $this->clock->now();
            $duration = $this->movementPolicy->duration($movement, $receivedAt);

            $deliveryInspection = $this->inspections->findByMovementAndType($movement->id, new InspectionType('entrega'))
                ?? throw new OpenMovementNotFoundException('Inspeccion de entrega no encontrada.');
            $receptionInspection = $this->inspections->createInspection(new ScannerInspectionCreateData(
                $movement->id,
                $scanner->id,
                new InspectionType('recepcion'),
                $receivedAt,
                $actor,
                $command->battery,
                $command->rating,
                $command->observations,
                details: $command->details,
            ));
            $differences = $this->comparison->compare(
                $this->inspections->listDetailsByInspectionId($deliveryInspection->id),
                $this->inspections->listDetailsByInspectionId($receptionInspection->id),
            );
            $target = new ScannerStatus($this->hasDamage($differences) ? 'pendiente_reparacion' : 'disponible');
            $this->stateMachine->assertTransition($scanner->status, $target);
            $this->movements->closeAsReturned($movement->id, $receivedAt, $actor->userId, $command->receiverName, $duration);
            $this->persistEvidence($command->evidenceReferences, $scanner->id, $movement->id, $receptionInspection->id, $actor);
            $this->scanners->changeStatus($scanner->id, $target, $actor->userId);
            $auditId = $this->audit->record('scanner.reception', 'scanner_movement', $movement->id, ['status' => $scanner->status->value], ['status' => $target->value], $actor, $context, ['duration_seconds' => $duration, 'differences' => count($differences)]);
            $updatedScanner = $this->scanners->findById($scanner->id) ?? throw new ScannerNotFoundException('Scanner no encontrado despues de la recepcion.');
            $closedMovement = $this->movements->findById($movement->id) ?? throw new OpenMovementNotFoundException('Movimiento cerrado no encontrado.');

            return new ReceptionResult($updatedScanner, $closedMovement, $deliveryInspection, $receptionInspection, $differences, $duration, [], $target, $auditId);
        });
    }

    private function hasDamage(array $differences): bool
    {
        foreach ($differences as $difference) {
            $state = is_array($difference->after) ? mb_strtolower((string) ($difference->after['estado'] ?? '')) : '';
            if ($difference->result === 'empeoro' && in_array($state, ['danado', 'dañado', 'no funciona', 'faltante'], true)) {
                return true;
            }
        }
        return false;
    }

    private function persistEvidence(array $references, int $scannerId, int $movementId, int $inspectionId, AuthenticatedActorData $actor): void
    {
        foreach ($references as $reference) {
            if (!$reference instanceof ScannerEvidenceMetadata) {
                throw new \InvalidArgumentException('Referencia de evidencia invalida.');
            }
            $this->evidence->create(new ScannerEvidenceMetadata($scannerId, $reference->type, $reference->storagePath, $reference->mimeType, $reference->sizeBytes, $reference->sha256, $reference->capturedAt, $actor, $movementId, $inspectionId));
        }
    }
}
