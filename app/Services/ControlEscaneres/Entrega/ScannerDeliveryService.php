<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Entrega;

use App\Domain\ControlEscaneres\{InspectionType, ScannerStatus};
use App\DTO\ControlEscaneres\{AuthenticatedActorData, BusinessRequestContext, DeliveryResult, ScannerEvidenceMetadata, ScannerInspectionCreateData, ScannerMovementCreateData};
use App\Exceptions\ControlEscaneres\ScannerNotFoundException;
use App\Repositories\ControlEscaneres\Contracts\{EvidenceRepositoryInterface, ScannerInspectionRepositoryInterface, ScannerMovementRepositoryInterface, ScannerRepositoryInterface, TransactionManagerInterface};
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Shared\{OperationalFolioGeneratorInterface, ScannerStateMachineInterface};
use App\Services\ControlEscaneres\Validaciones\{MovementPolicy, ScannerAvailabilityPolicy};

final class ScannerDeliveryService
{
    public function __construct(
        private ScannerRepositoryInterface $scanners,
        private ScannerMovementRepositoryInterface $movements,
        private ScannerInspectionRepositoryInterface $inspections,
        private EvidenceRepositoryInterface $evidence,
        private TransactionManagerInterface $transactions,
        private ScannerStateMachineInterface $stateMachine,
        private OperationalFolioGeneratorInterface $folios,
        private ScannerAuditService $audit,
        private ScannerAvailabilityPolicy $availability,
        private MovementPolicy $movementPolicy,
    ) {}

    public function deliver(ScannerMovementCreateData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): DeliveryResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): DeliveryResult {
            $scanner = $this->scanners->lockScannerForUpdate($command->scannerId);
            if ($scanner === null) {
                throw new ScannerNotFoundException('Scanner no encontrado.');
            }

            $this->availability->assertActive($scanner);
            $target = new ScannerStatus('entregado');
            $this->stateMachine->assertTransition($scanner->status, $target);
            $this->movementPolicy->assertNoOpen($this->movements->hasOpenMovement($scanner->id));

            $movement = $this->movements->create(new ScannerMovementCreateData(
                $scanner->id,
                $this->folios->generate(),
                $command->personName,
                $command->employeeNumber,
                $command->shift,
                $command->deliveredAt,
                $actor,
                $command->battery,
                $command->rating,
                $command->observations,
                $command->details,
                $command->evidenceReferences,
            ));
            $inspection = $this->inspections->createInspection(new ScannerInspectionCreateData(
                $movement->id,
                $scanner->id,
                new InspectionType('entrega'),
                $command->deliveredAt,
                $actor,
                $command->battery,
                $command->rating,
                $command->observations,
                details: $command->details,
            ));
            $this->persistEvidence($command->evidenceReferences, $scanner->id, $movement->id, $inspection->id, $actor);
            $this->scanners->changeStatus($scanner->id, $target, $actor->userId);
            $auditId = $this->audit->record('scanner.delivery', 'scanner_movement', $movement->id, ['status' => $scanner->status->value], ['status' => $target->value], $actor, $context, ['folio' => $movement->folio->value]);
            $updated = $this->scanners->findById($scanner->id) ?? throw new ScannerNotFoundException('Scanner no encontrado despues de la entrega.');

            return new DeliveryResult($updated, $movement, $inspection, $auditId, $target);
        });
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
