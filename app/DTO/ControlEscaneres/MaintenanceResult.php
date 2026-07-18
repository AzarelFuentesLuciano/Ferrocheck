<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\{Scanner,ScannerStatus};final readonly class MaintenanceResult{public function __construct(public Scanner$scanner,public ScannerStatus$previousStatus,public ScannerStatus$newStatus,public?int$incidentId,public int$auditEventId){}}
