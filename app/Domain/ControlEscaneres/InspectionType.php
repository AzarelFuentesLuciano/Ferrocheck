<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class InspectionType extends ValueObject {public function __construct(public string $value){if(!in_array($value,['entrega','recepcion'],true))throw new \InvalidArgumentException('Tipo inválido.');}}
