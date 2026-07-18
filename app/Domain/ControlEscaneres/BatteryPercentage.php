<?php
declare(strict_types=1); namespace App\Domain\ControlEscaneres;
final readonly class BatteryPercentage {public function __construct(public int $value){if($value<0||$value>100)throw new \InvalidArgumentException('Rango inválido.');}}
