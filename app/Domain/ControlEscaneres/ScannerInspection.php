<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres; final readonly class ScannerInspection {public function __construct(public int $id,public int $movementId,public InspectionType $type){}}
