<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres;
final readonly class ScannerInspectionDetailData {public const COMPONENTS=['bateria','pantalla','touch','botones','lector','wifi','datos_moviles','accesorios'];public function __construct(public string $component,public string $status,public ?float $numericValue=null,public ?string $textValue=null){if(!in_array($component,self::COMPONENTS,true))throw new \InvalidArgumentException('Componente inválido.');}}
