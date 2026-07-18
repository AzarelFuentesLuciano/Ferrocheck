<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\IncidentSeverity;
final readonly class IncidentSeverityChangeData{public function __construct(public int$incidentId,public IncidentSeverity$previousSeverity,public IncidentSeverity$newSeverity,public string$reason){if($incidentId<1||trim($reason)==='')throw new \InvalidArgumentException('Cambio inválido.');}}
