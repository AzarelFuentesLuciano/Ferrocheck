<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class ScannerReceptionFormViewModel{public function __construct(public?int$scannerId,public?string$scannerCode,public?int$movementId,public?string$custodian,public?string$deliveredAt,public string$csrfToken,public array$components,public array$messages=[]){}}
