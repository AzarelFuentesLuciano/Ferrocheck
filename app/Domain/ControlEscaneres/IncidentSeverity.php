<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class IncidentSeverity extends ValueObject {public function __construct(public string $value){if(!in_array($value,['baja','media','alta','critica'],true))throw new \InvalidArgumentException('Severidad inválida.');}}
