<?php
declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

final readonly class ScannerEvidenceMetadata
{
    public function __construct(
        public ?int $scannerId,
        public string $type,
        public string $storagePath,
        public string $mimeType,
        public int $sizeBytes,
        public string $sha256,
        public \DateTimeImmutable $capturedAt,
        public AuthenticatedActorData $actor,
        public ?int $movementId = null,
        public ?int $inspectionId = null,
        public ?int $incidentId = null,
        public ?int $id = null,
        public ?int $maintenanceId = null,
    ) {
        $ids = [$scannerId, $movementId, $inspectionId, $incidentId, $maintenanceId];
        if (!array_filter($ids, static fn($value): bool => is_int($value) && $value > 0)
            || array_filter($ids, static fn($value): bool => $value !== null && $value < 1)
            || ($id !== null && $id < 1)
            || trim($type) === ''
            || trim($storagePath) === ''
            || trim($mimeType) === ''
            || $sizeBytes < 0
            || !preg_match('/^[a-f0-9]{64}$/i', $sha256)) {
            throw new \InvalidArgumentException('Evidencia inválida.');
        }
    }
}
