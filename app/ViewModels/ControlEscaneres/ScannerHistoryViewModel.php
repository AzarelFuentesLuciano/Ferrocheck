<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class ScannerHistoryViewModel{public function __construct(public array$scanner,public array$movements,public array$inspections,public array$incidents,public array$evidences,public array$auditEvents,public array$timeline,public array$messages=[]){}}
