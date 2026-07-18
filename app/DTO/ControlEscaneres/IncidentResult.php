<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\{ScannerIncident,ScannerStatus};final readonly class IncidentResult{public function __construct(public ScannerIncident$incident,public ScannerStatus$scannerStatus,public array$evidenceIds,public int$auditEventId){}}
