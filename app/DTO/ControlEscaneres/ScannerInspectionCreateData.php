<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres; use App\Domain\ControlEscaneres\InspectionType;
final readonly class ScannerInspectionCreateData {public function __construct(public int $movementId,public int $scannerId,public InspectionType $type,public \DateTimeImmutable $inspectedAt,public AuthenticatedActorData $actor,public array $details=[]){foreach($details as $d)if(!$d instanceof ScannerInspectionDetailData)throw new \InvalidArgumentException('Detalle inválido.');}}
