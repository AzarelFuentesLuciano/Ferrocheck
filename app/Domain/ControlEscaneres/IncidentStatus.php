<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class IncidentStatus extends ValueObject {public function __construct(public string $value){if(!in_array($value,['abierta','en_revision','en_mantenimiento','resuelta','descartada'],true))throw new \InvalidArgumentException('Estado inválido.');}}
