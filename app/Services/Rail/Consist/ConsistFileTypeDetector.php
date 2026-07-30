<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use InvalidArgumentException;

final class ConsistFileTypeDetector
{
    public function __construct(
        private array $configuration,
        private ?ConsistHeaderNormalizer $normalizer = null,
    ) {
        $this->normalizer ??= new ConsistHeaderNormalizer();
    }

    public function detect(string $expectedType, array $headers): ConsistFileTypeDetection
    {
        $definitions = $this->configuration['files'] ?? [];
        if (!isset($definitions[$expectedType])) {
            throw new InvalidArgumentException('El tipo esperado de Consist Rail no está configurado.');
        }

        $normalizedHeaders = array_values(array_unique(array_filter(
            array_map(fn (mixed $header): string => $this->normalizer->normalize($header), $headers),
            static fn (string $header): bool => $header !== '',
        )));
        $matches = [];
        $missingByType = [];
        $qualified = [];

        foreach ($definitions as $type => $definition) {
            $identityHeaders = array_values(array_unique(array_map(
                fn (mixed $header): string => $this->normalizer->normalize($header),
                $definition['identity_headers'] ?? [],
            )));
            $matched = array_values(array_intersect($identityHeaders, $normalizedHeaders));
            $matches[$type] = count($matched);
            $missingByType[$type] = array_values(array_diff($identityHeaders, $normalizedHeaders));
            if ($matches[$type] >= (int) ($definition['minimum_identity_matches'] ?? 1)) {
                $qualified[] = $type;
            }
        }

        $highestScore = $matches === [] ? 0 : max($matches);
        $leaders = array_keys(array_filter(
            $matches,
            static fn (int $score): bool => $score === $highestScore,
        ));
        $isUnknown = $qualified === [];
        $isAmbiguous = !$isUnknown
            && count(array_intersect($leaders, $qualified)) > 1;
        $detectedType = !$isAmbiguous && !$isUnknown ? ($leaders[0] ?? null) : null;
        $expectedDefinition = $definitions[$expectedType];

        return new ConsistFileTypeDetection(
            expectedType: $expectedType,
            detectedType: $detectedType,
            isValid: $detectedType === $expectedType,
            isAmbiguous: $isAmbiguous,
            isUnknown: $isUnknown,
            matches: $matches,
            missingIdentityHeaders: $missingByType[$expectedType] ?? [],
            matchedIdentityHeaders: $matches[$expectedType] ?? 0,
            totalIdentityHeaders: count($expectedDefinition['identity_headers'] ?? []),
        );
    }
}
