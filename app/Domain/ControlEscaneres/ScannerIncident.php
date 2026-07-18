<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres; final readonly class ScannerIncident {public function __construct(public int $id,public IncidentSeverity $severity,public IncidentStatus $status){}}
