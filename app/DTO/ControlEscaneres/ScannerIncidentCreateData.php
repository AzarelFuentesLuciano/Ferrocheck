<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres; use App\Domain\ControlEscaneres\IncidentSeverity;
final readonly class ScannerIncidentCreateData {public function __construct(public int $scannerId,public string $type,public IncidentSeverity $severity,public string $description,public \DateTimeImmutable $reportedAt,public AuthenticatedActorData $actor){}}
