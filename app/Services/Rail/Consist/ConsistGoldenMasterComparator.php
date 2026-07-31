<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use RuntimeException;

final class ConsistGoldenMasterComparator
{
    public function __construct(private string $goldenMasterPath, private int $differenceLimit = 25)
    {
    }

    public function compare(ConsistDraftResult|array $draft): array
    {
        $generated = $draft instanceof ConsistDraftResult ? $draft->toArray() : $draft;
        $golden = json_decode((string) file_get_contents($this->goldenMasterPath), true);
        if (!is_array($golden) || !isset($golden['rows'])) {
            throw new RuntimeException('El golden master de Consist no está disponible.');
        }
        $units = array_values((array) ($generated['units'] ?? []));
        $differences = [];
        $matches = 0;
        $count = max(count($units), count($golden['rows']));
        for ($index = 0; $index < $count; $index++) {
            $actual = $units[$index] ?? null;
            $expectedRow = $golden['rows'][$index] ?? null;
            $expected = is_array($expectedRow)
                ? array_column((array) ($expectedRow['cells'] ?? []), 'cached_value', 'header')
                : null;
            $actualColumns = is_array($actual) ? ($actual['final_columns'] ?? null) : null;
            if ($actual !== null
                && $expected !== null
                && ($actual['global_position'] ?? null) === ($expectedRow['position'] ?? null)
                && ($actual['vin'] ?? null) === ($expectedRow['vin'] ?? null)
                && $this->sameColumns((array) $actualColumns, (array) $expected)
            ) {
                $matches++;
                continue;
            }
            if (count($differences) < $this->differenceLimit) {
                $differences[] = [
                    'position' => $index + 1,
                    'expected_vin' => $expectedRow['vin'] ?? null,
                    'actual_vin' => $actual['vin'] ?? null,
                    'columns' => $this->columnDifferences((array) $actualColumns, (array) $expected),
                ];
            }
        }
        return [
            'total_compared' => $count,
            'matches' => $matches,
            'differences' => $count - $matches,
            'first_differences' => $differences,
            'equivalence_percentage' => $count === 0 ? 100.0 : round(($matches / $count) * 100, 4),
            'pass' => $count === count($golden['rows']) && $matches === count($golden['rows']),
        ];
    }

    private function sameColumns(array $actual, array $expected): bool
    {
        return $this->columnDifferences($actual, $expected) === [];
    }

    private function columnDifferences(array $actual, array $expected): array
    {
        $differences = [];
        foreach (ConsistDocumentBuilder::HEADERS as $header) {
            if ((string) ($actual[$header] ?? '') !== (string) ($expected[$header] ?? '')) {
                $differences[$header] = ['expected' => $expected[$header] ?? null, 'actual' => $actual[$header] ?? null];
            }
        }
        return $differences;
    }
}
