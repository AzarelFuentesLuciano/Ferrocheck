<?php
declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

use App\Domain\ControlEscaneres\{BatteryPercentage, ScannerFolio};

final readonly class ScannerMovementCreateData
{
    public function __construct(
        public int $scannerId,
        public ScannerFolio $folio,
        public string $personName,
        public string $employeeNumber,
        public string $shift,
        public \DateTimeImmutable $deliveredAt,
        public AuthenticatedActorData $actor,
        public ?BatteryPercentage $battery = null,
        public ?int $rating = null,
        public ?string $observations = null,
        public array $details = [],
        public array $evidenceReferences = [],
    ) {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('Calificacion invalida.');
        }
    }
}
