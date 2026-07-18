<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class ScannerOperationResultViewModel{public function __construct(public bool$ok,public string$message,public?string$folio=null,public?string$resultingStatus=null,public?int$durationSeconds=null,public array$differences=[]){}}
