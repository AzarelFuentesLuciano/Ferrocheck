<?php
declare(strict_types=1);

namespace App\DTO\ControlEscaneres;

use App\Domain\ControlEscaneres\{ScannerCode, ScannerStatus};

final readonly class ScannerCreateData
{
    public function __construct(
        public ScannerCode $code,
        public string $qr,
        public string $brand,
        public string $model,
        public ScannerStatus $status,
        public ?string $serial = null,
        public ?string $imei = null,
        public ?string $iccid = null,
        public ?int $areaId = null,
        public ?string $tag = null,
        public ?string $phone = null,
        public ?string $network = null,
        public ?string $plan = null,
        public ?string $activity = null,
        public ?string $area = null,
        public ?string $location = null,
        public ?string $age = null,
        public ?string $observations = null,
        public ?string $mainPhoto = null,
        public bool $active = true,
        public ?int $organizationalAreaId = null,
    ) {
        if (trim($brand) === '' || trim($model) === '') {
            throw new \InvalidArgumentException('Marca y modelo son obligatorios.');
        }
    }
}
