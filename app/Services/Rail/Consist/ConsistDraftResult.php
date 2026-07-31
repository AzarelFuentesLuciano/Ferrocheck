<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final readonly class ConsistDraftResult
{
    public function __construct(
        public string $analysisToken,
        public string $generatedAt,
        public int $createdBy,
        public string $startDate,
        public string $endDate,
        public string $status,
        public array $catalogVersion,
        public array $platforms,
        public array $units,
        public array $issues,
        public int $cnacsDuplicateCount,
        public array $operationalSummary,
    ) {
    }

    public function toArray(): array
    {
        return [
            'analysis_token' => $this->analysisToken,
            'generated_at' => $this->generatedAt,
            'created_by' => $this->createdBy,
            'fecha_inicio' => $this->startDate,
            'fecha_fin' => $this->endDate,
            'status' => $this->status,
            'catalog_version' => $this->catalogVersion,
            'total_units' => count($this->units),
            'total_platforms' => count($this->platforms),
            'platforms' => $this->platforms,
            'units' => $this->units,
            'issues' => $this->issues,
            'cnacs_duplicate_count' => $this->cnacsDuplicateCount,
            'operational_summary' => $this->operationalSummary,
        ];
    }
}
