<?php
declare(strict_types=1);namespace App\ViewModels\ControlEscaneres;final readonly class DashboardAlertViewModel{public function __construct(public string$scannerCode,public string$situation,public string$severity,public string$occurredAt,public string$url){}}
