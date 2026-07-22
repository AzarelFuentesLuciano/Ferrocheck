<?php

declare(strict_types=1);

namespace App\ViewModels\ControlEscaneres;

final readonly class ScannerReceptionFormViewModel
{
    public function __construct(
        public ?int $scannerId,
        public ?string $scannerCode,
        public ?int $movementId,
        public ?string $custodian,
        public ?string $deliveredAt,
        public string $csrfToken,
        public array $components,
        public array $messages = [],
        public array $deliveryDetails = [],
        public ?int $deliveryBattery = null,
        public ?int $deliveryRating = null,
        public ?string $deliveryObservations = null,
        public array $deliveryPhotos = [],
        public ?string $scannerTag = null,
        public ?string $employeeNumber = null,
        public ?string $areaName = null,
        public ?string $supervisorName = null,
        public ?string $deliveryResponsibleName = null,
        public ?string $shift = null,
    ) {
    }
}
