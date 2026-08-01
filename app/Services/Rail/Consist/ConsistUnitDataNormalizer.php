<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final class ConsistUnitDataNormalizer
{
    private ConsistHeaderNormalizer $headerNormalizer;

    public function __construct(?ConsistHeaderNormalizer $headerNormalizer = null)
    {
        $this->headerNormalizer = $headerNormalizer ?? new ConsistHeaderNormalizer();
    }

    public function finalData(array $finalData, array $vehicleLoadData = []): array
    {
        if (array_is_list($finalData)) {
            $finalData = array_combine(
                ConsistDocumentBuilder::HEADERS,
                array_pad(
                    array_slice($finalData, 0, count(ConsistDocumentBuilder::HEADERS)),
                    count(ConsistDocumentBuilder::HEADERS),
                    '',
                ),
            );
        }

        $normalized = [];
        foreach (ConsistDocumentBuilder::HEADERS as $header) {
            $normalized[$header] = $finalData[$header] ?? '';
        }
        if (trim((string) $normalized['fdTransportationName1']) === '') {
            $normalized['fdTransportationName1'] = $this->vehicleValue(
                $vehicleLoadData,
                'fdTransportationName1',
            );
        }
        return $normalized;
    }

    public function vehicleValue(array $vehicleLoadData, string $canonicalHeader): string
    {
        if (array_key_exists($canonicalHeader, $vehicleLoadData)) {
            return trim((string) $vehicleLoadData[$canonicalHeader]);
        }

        $expected = $this->headerNormalizer->normalize($canonicalHeader);
        foreach ($vehicleLoadData as $header => $value) {
            if ($this->headerNormalizer->normalize((string) $header) === $expected) {
                return trim((string) $value);
            }
        }
        return '';
    }
}
