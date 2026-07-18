<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class DashboardActivityItemViewModel{public function __construct(public string$title,public string$scannerCode,public string$occurredAt,public?string$folio,public string$url){}}
