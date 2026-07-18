<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class ScannerCode extends ValueObject { public function __construct(public string $value){if(!preg_match('/^SC-[0-9]{4,}$/',$value))throw new \InvalidArgumentException('Código inválido.');} }
