<?php
declare(strict_types=1);namespace App\DTO\ControlEscaneres;final readonly class DashboardSummary{public function __construct(public array$metrics,public array$recentMovements,public array$topIncidentScanners,public array$topAreas,public array$topUsers,public \DateTimeImmutable$generatedAt){}}
