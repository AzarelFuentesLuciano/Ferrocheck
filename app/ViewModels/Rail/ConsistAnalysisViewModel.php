<?php

declare(strict_types=1);

namespace App\ViewModels\Rail;

final readonly class ConsistAnalysisViewModel
{
    public function __construct(public array $result)
    {
    }
}
