<?php

declare(strict_types=1);

namespace App\ViewModels\Rail;

final readonly class ConsistUploadViewModel
{
    public function __construct(
        public string $csrfToken,
        public array $messages,
        public ?array $preview,
        public array $configuration,
        public string $batchToken = '',
        public bool $canAnalyze = false,
        public ?ConsistAnalysisViewModel $analysis = null,
    ) {
    }
}
