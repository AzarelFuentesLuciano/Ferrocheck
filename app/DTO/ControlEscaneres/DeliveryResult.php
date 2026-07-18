<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;use App\Domain\ControlEscaneres\{Scanner,ScannerMovement,ScannerInspection,ScannerStatus};final readonly class DeliveryResult{public function __construct(public Scanner$scanner,public ScannerMovement$movement,public ScannerInspection$inspection,public int$auditEventId,public ScannerStatus$resultingStatus){}}
