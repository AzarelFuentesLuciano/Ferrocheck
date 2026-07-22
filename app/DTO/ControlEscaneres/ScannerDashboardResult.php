<?php
declare(strict_types=1);
namespace App\DTO\ControlEscaneres;
final readonly class ScannerDashboardResult
{
    public function __construct(public DashboardRange$range,public ScannerInventorySummary$inventory,public ScannerIncidentSummary$incidents,public array$statuses,public array$attention,public array$activity,public array$trend,public int$deliveriesInRange,public int$receptionsInRange,public array$metrics=[],public array$analytics=[]) {}
}
