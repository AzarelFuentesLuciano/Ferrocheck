<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class MovementStatus extends ValueObject {public const VALUES=['abierto','devuelto','vencido','con_incidencia','cancelado'];public function __construct(public string $value){if(!in_array($value,self::VALUES,true))throw new \InvalidArgumentException('Estado inválido.');}}
