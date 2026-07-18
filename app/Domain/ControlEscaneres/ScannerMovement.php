<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres; final readonly class ScannerMovement {public function __construct(public int $id,public int $scannerId,public ScannerFolio $folio,public MovementStatus $status){}}
