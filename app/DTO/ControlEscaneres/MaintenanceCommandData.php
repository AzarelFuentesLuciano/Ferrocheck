<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;
final readonly class MaintenanceCommandData{public function __construct(public int$scannerId,public string$action,public string$reason,public?string$observations=null,public array$evidenceReferences=[],public?\DateTimeImmutable$effectiveAt=null){if($scannerId<1||!in_array($action,['send','return'],true)||trim($reason)==='')throw new \InvalidArgumentException('Mantenimiento inválido.');}}
