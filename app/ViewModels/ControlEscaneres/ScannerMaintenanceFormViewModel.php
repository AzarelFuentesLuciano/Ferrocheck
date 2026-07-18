<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class ScannerMaintenanceFormViewModel{public function __construct(public?int$scannerId,public?string$scannerCode,public?string$status,public array$allowedStatuses,public string$csrfToken,public array$messages=[]){}}
