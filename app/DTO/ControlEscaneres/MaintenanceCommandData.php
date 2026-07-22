<?php
declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

use App\Domain\ControlEscaneres\ScannerStatus;

final readonly class MaintenanceCommandData
{
    public function __construct(
        public int $scannerId,
        public string $action,
        public string $reason,
        public ?string $observations = null,
        public array $evidenceReferences = [],
        public ?\DateTimeImmutable $effectiveAt = null,
        public ?ScannerStatus $resultingStatus = null,
        public ?string $technician = null,
        public ?string $diagnosis = null,
        public ?float $cost = null,
        public ?string $estimatedDate = null,
        public ?string $result = null,
    ) {
        if ($scannerId < 1 || !in_array($action, ['send', 'return'], true) || trim($reason) === '') throw new \InvalidArgumentException('Mantenimiento inválido.');
        if ($cost !== null && $cost < 0) throw new \InvalidArgumentException('El costo no puede ser negativo.');
        if ($estimatedDate !== null && \DateTimeImmutable::createFromFormat('Y-m-d', $estimatedDate) === false) throw new \InvalidArgumentException('Fecha estimada inválida.');
    }
}
