<?php
declare(strict_types=1);namespace App\Repositories\ControlEscaneres\Contracts;use App\DTO\ControlEscaneres\{DashboardRange,ScannerDashboardResult};interface ScannerDashboardQueryInterface{public function fetch(DashboardRange$range):ScannerDashboardResult;}
