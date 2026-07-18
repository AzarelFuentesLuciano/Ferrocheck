<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class ScannerDeliveryFormViewModel{public function __construct(public?int$scannerId,public?string$scannerCode,public?string$status,public string$csrfToken,public array$components,public array$messages=[]){}}
