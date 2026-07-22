<?php
declare(strict_types=1);
namespace App\ViewModels\ControlEscaneres;
final readonly class ScannerDashboardViewModel
{
    public function __construct(public string$rangeKey,public string$rangeLabel,public string$updatedAt,public array$kpis,public array$statuses,public array$alerts,public array$activity,public array$trend,public array$quickActions,public int$deliveriesInRange,public int$receptionsInRange,public bool$hasTrend,public array$analytics=[]) {}
}
