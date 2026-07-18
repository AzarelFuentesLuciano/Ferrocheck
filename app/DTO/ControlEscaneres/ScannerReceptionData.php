<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\BatteryPercentage;
final readonly class ScannerReceptionData{public function __construct(public int$movementId,public int$scannerId,public string$receiverName,public?BatteryPercentage$battery,public?int$rating,public?string$observations,public array$details=[],public array$evidenceReferences=[],public?\DateTimeImmutable$effectiveAt=null){if($movementId<1||$scannerId<1||trim($receiverName)===''||($rating!==null&&($rating<1||$rating>5)))throw new \InvalidArgumentException('Recepción inválida.');}}
