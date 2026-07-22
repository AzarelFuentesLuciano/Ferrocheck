<?php

declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Contracts;

use App\DTO\ControlEscaneres\ScannerIncidentCreateData;
use App\Domain\ControlEscaneres\{IncidentSeverity, IncidentStatus, ScannerIncident};

interface ScannerIncidentRepositoryInterface
{
    public function create(ScannerIncidentCreateData $data): ScannerIncident;
    public function findById(int $id): ?ScannerIncident;
    public function listByScannerId(int $id): array;
    public function listByMovementId(int $id): array;
    public function listByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
    public function listOpen(): array;
    public function changeStatus(int $id, IncidentStatus $status, int $actor): void;
    public function changeSeverity(int $id, IncidentSeverity $severity, int $actor): void;
    public function addFollowUp(int $id, ?string $previousStatus, string $newStatus, string $comment, int $actor): void;
    public function listFollowUps(int $id): array;
    public function resolve(int $id, string $resolution, int $actor, \DateTimeImmutable $at): void;
    public function cancel(int $id, string $reason, int $actor, \DateTimeImmutable $at): void;
    public function countOpenBySeverity(IncidentSeverity $severity): int;
}
