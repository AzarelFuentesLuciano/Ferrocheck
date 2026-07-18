<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class ScannerInventorySummary{public function __construct(public int$total,public int$active,public int$inactive,public int$available,public int$delivered,public int$maintenance){}}
