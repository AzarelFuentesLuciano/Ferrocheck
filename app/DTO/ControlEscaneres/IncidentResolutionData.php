<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\ScannerStatus;
final readonly class IncidentResolutionData{public function __construct(public int$incidentId,public string$resolution,public ScannerStatus$resultingScannerStatus,public array$evidenceReferences=[],public?\DateTimeImmutable$resolvedAt=null){if($incidentId<1||trim($resolution)==='')throw new \InvalidArgumentException('Resolución inválida.');}}
