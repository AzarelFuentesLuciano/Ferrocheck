<?php
declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

use App\Domain\ControlEscaneres\IncidentSeverity;

final readonly class ScannerIncidentCreateData
{
    public function __construct(
        public int $scannerId,
        public string $type,
        public IncidentSeverity $severity,
        public string $description,
        public \DateTimeImmutable $reportedAt,
        public AuthenticatedActorData $actor,
        public ?int $movementId = null,
        public array $evidenceReferences = [],
    ) {
        if ($movementId !== null && $movementId < 1) {
            throw new \InvalidArgumentException('Movimiento invalido.');
        }
    }
}
