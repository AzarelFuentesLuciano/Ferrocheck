<?php

declare(strict_types=1);

namespace App\ViewModels\ControlEscaneres;

final readonly class ScannerIncidentFormViewModel
{
    public function __construct(public ?int $scannerId, public ?string $scannerCode, public ?int $movementId, public array $incidents, public array $allowedStatuses, public string $csrfToken, public array $messages = [], public array $followUps = [])
    {
    }
}
