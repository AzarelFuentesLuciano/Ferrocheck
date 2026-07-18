<?php
declare(strict_types=1);namespace App\Domain\ControlEscaneres;
final readonly class ScannerInspection{public function __construct(public int$id,public int$movementId,public int$scannerId,public InspectionType$type,public?BatteryPercentage$battery,public?int$rating,public?string$observations,public?int$firmaUsuarioEvidenciaId,public?int$firmaResponsableEvidenciaId,public \DateTimeImmutable$inspectedAt,public?int$registeredBy,public \DateTimeImmutable$createdAt,public \DateTimeImmutable$updatedAt){}}
