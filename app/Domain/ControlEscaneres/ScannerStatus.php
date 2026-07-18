<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class ScannerStatus extends ValueObject {public const VALUES=['disponible','entregado','mantenimiento','pendiente_reparacion','baja_definitiva','extraviado'];public function __construct(public string $value){if(!in_array($value,self::VALUES,true))throw new \InvalidArgumentException('Estado inválido.');}}
