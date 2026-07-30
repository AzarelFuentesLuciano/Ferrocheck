<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final readonly class ConsistFileTypeDetection
{
    public function __construct(
        public string $expectedType,
        public ?string $detectedType,
        public bool $isValid,
        public bool $isAmbiguous,
        public bool $isUnknown,
        public array $matches,
        public array $missingIdentityHeaders,
        public int $matchedIdentityHeaders,
        public int $totalIdentityHeaders,
    ) {
    }

    public function toArray(): array
    {
        return [
            'expected_type' => $this->expectedType,
            'detected_type' => $this->detectedType,
            'is_valid' => $this->isValid,
            'is_ambiguous' => $this->isAmbiguous,
            'is_unknown' => $this->isUnknown,
            'matches' => $this->matches,
            'missing_identity_headers' => $this->missingIdentityHeaders,
            'matched_identity_headers' => $this->matchedIdentityHeaders,
            'total_identity_headers' => $this->totalIdentityHeaders,
        ];
    }
}
