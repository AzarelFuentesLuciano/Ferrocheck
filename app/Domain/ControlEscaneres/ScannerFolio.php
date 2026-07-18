<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class ScannerFolio extends ValueObject {public function __construct(public string $value){if(!preg_match('/^MOV-[0-9]{8}-[A-Z0-9]{6,20}$/',$value))throw new \InvalidArgumentException('Folio inválido.');}}
