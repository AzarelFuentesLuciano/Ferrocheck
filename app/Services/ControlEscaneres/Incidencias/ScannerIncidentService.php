<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Incidencias;

use App\DTO\ControlEscaneres\{AuthenticatedActorData, BusinessRequestContext, IncidentResolutionData, IncidentResult, IncidentSeverityChangeData, ScannerEvidenceMetadata, ScannerIncidentCreateData};
use App\Exceptions\ControlEscaneres\{IncidentAlreadyResolvedException, ScannerNotFoundException};
use App\Domain\ControlEscaneres\IncidentStatus;
use App\Repositories\ControlEscaneres\Contracts\{EvidenceRepositoryInterface, ScannerIncidentRepositoryInterface, ScannerRepositoryInterface, TransactionManagerInterface};
use App\Services\ControlEscaneres\Auditoria\ScannerAuditService;
use App\Services\ControlEscaneres\Shared\{BusinessClockInterface, ScannerStateMachineInterface};
use App\Services\ControlEscaneres\Validaciones\{IncidentPolicy, ScannerAvailabilityPolicy};

final class ScannerIncidentService
{
    public function __construct(
        private ScannerRepositoryInterface $scanners,
        private ScannerIncidentRepositoryInterface $incidents,
        private EvidenceRepositoryInterface $evidence,
        private TransactionManagerInterface $transactions,
        private ScannerStateMachineInterface $stateMachine,
        private BusinessClockInterface $clock,
        private ScannerAuditService $audit,
        private ScannerAvailabilityPolicy $availability,
        private IncidentPolicy $policy,
    ) {}

    public function report(ScannerIncidentCreateData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): IncidentResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): IncidentResult {
            $scanner = $this->scanners->lockScannerForUpdate($command->scannerId);
            if ($scanner === null) {
                throw new ScannerNotFoundException('Scanner no encontrado.');
            }
            $this->availability->assertActive($scanner);
            $incident = $this->incidents->create(new ScannerIncidentCreateData($scanner->id, $command->type, $command->severity, $command->description, $command->reportedAt, $actor, $command->movementId, $command->evidenceReferences));
            $target = $this->policy->resultingStatus($command->type, $command->severity, $scanner->status);
            if ($target->value !== $scanner->status->value) {
                $this->stateMachine->assertTransition($scanner->status, $target);
                $this->scanners->changeStatus($scanner->id, $target, $actor->userId);
            }
            $evidenceIds = $this->persistEvidence($command->evidenceReferences, $scanner->id, $command->movementId, $incident->id, $actor);
            $auditId = $this->audit->record('scanner.incident.report', 'scanner_incident', $incident->id, [], ['severity' => $incident->severity->value, 'status' => $incident->status->value], $actor, $context, ['scanner_id' => $scanner->id]);
            return new IncidentResult($incident, $target, $evidenceIds, $auditId);
        });
    }

    public function changeSeverity(IncidentSeverityChangeData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): IncidentResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): IncidentResult {
            $incident = $this->requireIncident($command->incidentId);
            $this->assertOpen($incident->status->value);
            if ($incident->severity->value !== $command->previousSeverity->value) {
                throw new \DomainException('La severidad previa no coincide.');
            }
            $scanner = $this->scanners->lockScannerForUpdate($incident->scannerId) ?? throw new ScannerNotFoundException('Scanner no encontrado.');
            $this->incidents->changeSeverity($incident->id, $command->newSeverity, $actor->userId);
            $target = $this->policy->resultingStatus($incident->type, $command->newSeverity, $scanner->status);
            if ($target->value !== $scanner->status->value) {
                $this->stateMachine->assertTransition($scanner->status, $target);
                $this->scanners->changeStatus($scanner->id, $target, $actor->userId);
            }
            $updated = $this->requireIncident($incident->id);
            $auditId = $this->audit->record('scanner.incident.severity', 'scanner_incident', $incident->id, ['severity' => $incident->severity->value], ['severity' => $updated->severity->value], $actor, $context, ['reason' => $command->reason]);
            return new IncidentResult($updated, $target, [], $auditId);
        });
    }

    public function resolve(IncidentResolutionData $command, AuthenticatedActorData $actor, BusinessRequestContext $context): IncidentResult
    {
        return $this->transactions->transactional(function () use ($command, $actor, $context): IncidentResult {
            $incident = $this->requireIncident($command->incidentId);
            $this->assertOpen($incident->status->value);
            $scanner = $this->scanners->lockScannerForUpdate($incident->scannerId) ?? throw new ScannerNotFoundException('Scanner no encontrado.');
            $resolvedAt = $command->resolvedAt ?? $this->clock->now();
            $this->incidents->resolve($incident->id, $command->resolution, $actor->userId, $resolvedAt);
            $this->incidents->addFollowUp($incident->id, $incident->status->value, 'resuelta', $command->resolution, $actor->userId);
            if ($command->resultingScannerStatus->value !== $scanner->status->value) {
                $this->stateMachine->assertTransition($scanner->status, $command->resultingScannerStatus);
                $this->scanners->changeStatus($scanner->id, $command->resultingScannerStatus, $actor->userId);
            }
            $evidenceIds = $this->persistEvidence($command->evidenceReferences, $scanner->id, $incident->movementId, $incident->id, $actor);
            $updated = $this->requireIncident($incident->id);
            $auditId = $this->audit->record('scanner.incident.resolve', 'scanner_incident', $incident->id, ['status' => $incident->status->value], ['status' => $updated->status->value], $actor, $context, ['resolution' => $command->resolution]);
            return new IncidentResult($updated, $command->resultingScannerStatus, $evidenceIds, $auditId);
        });
    }

    public function followUp(int $incidentId, string $comment, AuthenticatedActorData $actor, BusinessRequestContext $context): void
    {
        $comment = trim($comment);
        if ($comment === '' || mb_strlen($comment) > 2000) {
            throw new \InvalidArgumentException('El seguimiento es obligatorio y admite hasta 2000 caracteres.');
        }
        $this->transactions->transactional(function () use ($incidentId, $comment, $actor, $context): void {
            $incident = $this->requireIncident($incidentId);
            $this->assertOpen($incident->status->value);
            $this->incidents->changeStatus($incidentId, new IncidentStatus('en_seguimiento'), $actor->userId);
            $this->incidents->addFollowUp($incidentId, $incident->status->value, 'en_seguimiento', $comment, $actor->userId);
            $this->audit->record('scanner.incident.follow_up', 'scanner_incident', $incidentId, ['status' => $incident->status->value], ['status' => 'en_seguimiento'], $actor, $context, ['comment' => $comment]);
        });
    }

    public function cancel(int $incidentId, string $reason, AuthenticatedActorData $actor, BusinessRequestContext $context): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new \InvalidArgumentException('El motivo de cancelación es obligatorio y admite hasta 2000 caracteres.');
        }
        $this->transactions->transactional(function () use ($incidentId, $reason, $actor, $context): void {
            $incident = $this->requireIncident($incidentId);
            $this->assertOpen($incident->status->value);
            $at = $this->clock->now();
            $this->incidents->cancel($incidentId, $reason, $actor->userId, $at);
            $this->incidents->addFollowUp($incidentId, $incident->status->value, 'cancelada', $reason, $actor->userId);
            $this->audit->record('scanner.incident.cancel', 'scanner_incident', $incidentId, ['status' => $incident->status->value], ['status' => 'cancelada'], $actor, $context, ['reason' => $reason]);
        });
    }

    private function requireIncident(int $id): \App\Domain\ControlEscaneres\ScannerIncident
    {
        return $this->incidents->findById($id) ?? throw new \DomainException('Incidencia no encontrada.');
    }

    private function assertOpen(string $status): void
    {
        if (in_array($status, ['resuelta', 'cancelada'], true)) {
            throw new IncidentAlreadyResolvedException('La incidencia ya esta cerrada.');
        }
    }

    private function persistEvidence(array $references, int $scannerId, ?int $movementId, int $incidentId, AuthenticatedActorData $actor): array
    {
        $ids = [];
        foreach ($references as $reference) {
            if (!$reference instanceof ScannerEvidenceMetadata) {
                throw new \InvalidArgumentException('Referencia de evidencia invalida.');
            }
            $ids[] = $this->evidence->create(new ScannerEvidenceMetadata($scannerId, $reference->type, $reference->storagePath, $reference->mimeType, $reference->sizeBytes, $reference->sha256, $reference->capturedAt, $actor, $movementId, incidentId: $incidentId));
        }
        return $ids;
    }
}
