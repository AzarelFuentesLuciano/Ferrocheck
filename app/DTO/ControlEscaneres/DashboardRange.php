<?php
declare(strict_types=1);
namespace App\DTO\ControlEscaneres;
final readonly class DashboardRange{public const ALLOWED=['today'=>1,'7d'=>7,'30d'=>30];public function __construct(public string$key,public \DateTimeImmutable$from,public \DateTimeImmutable$to){if(!isset(self::ALLOWED[$key])||$from>$to)throw new \InvalidArgumentException('Rango de dashboard inválido.');}public static function fromInput(?string$key,\DateTimeImmutable$now):self{$key=isset(self::ALLOWED[$key??''])?$key:'today';$days=self::ALLOWED[$key];return new self($key,$now->setTime(0,0)->modify('-'.($days-1).' days'),$now);}}
