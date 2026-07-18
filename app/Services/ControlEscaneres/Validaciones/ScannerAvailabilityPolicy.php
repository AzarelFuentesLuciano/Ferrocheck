<?php
declare(strict_types=1);namespace App\Services\ControlEscaneres\Validaciones;use App\Domain\ControlEscaneres\Scanner;use App\Exceptions\ControlEscaneres\ScannerUnavailableException;final class ScannerAvailabilityPolicy{public function assertActive(Scanner$s):void{if(!$s->active)throw new ScannerUnavailableException('El escáner está inactivo.');}}
