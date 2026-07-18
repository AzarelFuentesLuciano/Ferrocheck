<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres;
final readonly class ScannerUpdateData {public function __construct(public string $qr,public string $brand,public string $model,public ?string $serial=null,public ?string $imei=null,public ?string $iccid=null,public ?int $areaId=null){}}
