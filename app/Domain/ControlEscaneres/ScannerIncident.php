<?php
declare(strict_types=1);namespace App\Domain\ControlEscaneres;
final readonly class ScannerIncident{public function __construct(public int$id,public int$scannerId,public?int$movementId,public string$type,public IncidentSeverity$severity,public string$description,public IncidentStatus$status,public?string$reportedByName,public?int$registeredBy,public \DateTimeImmutable$reportedAt,public?string$resolution,public?int$resolvedBy,public?\DateTimeImmutable$resolvedAt,public \DateTimeImmutable$createdAt,public \DateTimeImmutable$updatedAt){}}
