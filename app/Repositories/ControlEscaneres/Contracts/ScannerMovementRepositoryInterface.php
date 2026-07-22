<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Contracts;
use App\Domain\ControlEscaneres\ScannerMovement;
use App\DTO\ControlEscaneres\ScannerMovementCreateData;
interface ScannerMovementRepositoryInterface
{
    public function findById(int $id): ?ScannerMovement;
    public function findOpenByScannerId(int $id): ?ScannerMovement;
    public function hasOpenMovement(int $id): bool;
    public function create(ScannerMovementCreateData $data): ScannerMovement;
    public function closeAsReturned(int $id, \DateTimeImmutable $at, int $actor, string $receiver, int $durationSeconds, ?string $responsibleName = null): void;
    public function cancel(int $id, int $actor, string $reason): void;
    public function markOverdue(\DateTimeImmutable $at): int;
    public function listByScannerId(int $id): array;
    public function listOpen(): array;
    public function listByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
    public function listByActor(int $actorId): array;
    public function listByArea(int $areaId): array;
    public function countOpen(): int;
    public function countOverdue(\DateTimeImmutable $at): int;
    public function lockOpenMovementForUpdate(int $id): ?ScannerMovement;
}
