<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Mantenimiento;

use App\DTO\ControlEscaneres\{AuthenticatedActorData, BusinessRequestContext, MaintenanceCommandData, MaintenanceResult, ScannerEvidenceMetadata};
use App\Exceptions\ControlEscaneres\ScannerNotFoundException;
use App\Repositories\ControlEscaneres\Contracts\{EvidenceRepositoryInterface, ScannerMovementRepositoryInterface, ScannerRepositoryInterface, TransactionManagerInterface};
use App\Repositories\ControlEscaneres\Pdo\PdoScannerMaintenanceRepository;
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Shared\ScannerStateMachineInterface;
use App\Services\ControlEscaneres\Validaciones\{MaintenancePolicy, MovementPolicy, ScannerAvailabilityPolicy};

final class ScannerMaintenanceService
{
    public function __construct(
        private ScannerRepositoryInterface $scanners,
        private ScannerMovementRepositoryInterface $movements,
        private EvidenceRepositoryInterface $evidence,
        private TransactionManagerInterface $transactions,
        private ScannerStateMachineInterface $stateMachine,
        private ScannerAuditService $audit,
        private ScannerAvailabilityPolicy $availability,
        private MovementPolicy $movementPolicy,
        private MaintenancePolicy $policy,
        private ?PdoScannerMaintenanceRepository $maintenanceRecords = null,
    ) {}

    public function execute(MaintenanceCommandData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): MaintenanceResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): MaintenanceResult {
            $scanner = $this->scanners->lockScannerForUpdate($command->scannerId);
            if ($scanner === null) {
                throw new ScannerNotFoundException('Scanner no encontrado.');
            }
            $this->availability->assertActive($scanner);
            $this->movementPolicy->assertNoOpen($this->movements->hasOpenMovement($scanner->id));
            $target = $this->policy->target($command->action, $command->resultingStatus);
            $this->stateMachine->assertTransition($scanner->status, $target);
            $at = $command->effectiveAt ?? new \DateTimeImmutable();
            $maintenanceId = null;
            if ($this->maintenanceRecords !== null) {
                $maintenanceId = $command->action === 'send'
                    ? $this->maintenanceRecords->open($scanner->id, ['reason' => $command->reason, 'technician' => $command->technician, 'diagnosis' => $command->diagnosis ?? $command->observations, 'cost' => $command->cost, 'estimated' => $command->estimatedDate], $actor->userId, $at)
                    : $this->maintenanceRecords->close($scanner->id, $command->result ?? $command->reason, $target->value, $actor->userId, $at);
            }
            $this->persistEvidence($command->evidenceReferences, $scanner->id, $maintenanceId, $actor);
            $this->scanners->changeStatus($scanner->id, $target, $actor->userId);
            $auditId = $this->audit->record('scanner.maintenance.'.$command->action, 'scanner', $scanner->id, ['status' => $scanner->status->value], ['status' => $target->value], $actor, $context, ['reason' => $command->reason, 'observations' => $command->observations]);
            $updated = $this->scanners->findById($scanner->id) ?? throw new ScannerNotFoundException('Scanner no encontrado despues del mantenimiento.');
            return new MaintenanceResult($updated, $scanner->status, $target, $maintenanceId, $auditId);
        });
    }

    private function persistEvidence(array $references, int $scannerId, ?int $maintenanceId, AuthenticatedActorData $actor): void
    {
        foreach ($references as $reference) {
            if (!$reference instanceof ScannerEvidenceMetadata) {
                throw new \InvalidArgumentException('Referencia de evidencia invalida.');
            }
            $this->evidence->create(new ScannerEvidenceMetadata($scannerId, $reference->type, $reference->storagePath, $reference->mimeType, $reference->sizeBytes, $reference->sha256, $reference->capturedAt, $actor, maintenanceId: $maintenanceId));
        }
    }
}
