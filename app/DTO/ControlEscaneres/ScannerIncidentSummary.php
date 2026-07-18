<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class ScannerIncidentSummary{public function __construct(public int$openIncidents,public int$affectedScanners,public int$criticalIncidents){}}
