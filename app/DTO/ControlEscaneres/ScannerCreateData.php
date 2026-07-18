<?php
declare(strict_types=1); namespace App\DTO\ControlEscaneres; use App\Domain\ControlEscaneres\{ScannerCode,ScannerStatus};
final readonly class ScannerCreateData {public function __construct(public ScannerCode $code,public string $qr,public string $brand,public string $model,public ScannerStatus $status,public ?string $serial=null,public ?string $imei=null,public ?string $iccid=null,public ?int $areaId=null){}}
