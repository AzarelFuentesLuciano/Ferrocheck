<?php

declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

use App\Domain\ControlEscaneres\{BatteryPercentage, InspectionType};

final readonly class ScannerInspectionCreateData
{
    public function __construct(
        public int $movementId,
        public int $scannerId,
        public InspectionType $type,
        public \DateTimeImmutable $inspectedAt,
        public AuthenticatedActorData $actor,
        public ?BatteryPercentage $battery = null,
        public ?int $rating = null,
        public ?string $observations = null,
        public ?int $firmaUsuarioEvidenciaId = null,
        public ?int $firmaResponsableEvidenciaId = null,
        public array $details = [],
    ) {
        if ($rating !== null && ($rating < 0 || $rating > 100)) {
            throw new \InvalidArgumentException('Calificación inválida.');
        }
        foreach ($details as $detail) {
            if (!$detail instanceof ScannerInspectionDetailData) {
                throw new \InvalidArgumentException('Detalle inválido.');
            }
        }
    }
}
