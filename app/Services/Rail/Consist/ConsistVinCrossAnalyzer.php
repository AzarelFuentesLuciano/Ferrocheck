<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final class ConsistVinCrossAnalyzer
{
    private const CATEGORY_BY_MASK = [
        7 => 'present_in_all',
        1 => 'only_vehicle_load_report',
        2 => 'only_shippers',
        4 => 'only_cnacs',
        3 => 'vehicle_load_report_and_shippers',
        5 => 'vehicle_load_report_and_cnacs',
        6 => 'shippers_and_cnacs',
    ];

    public function __construct(private int $sampleLimit = 50)
    {
        $this->sampleLimit = max(0, min(50, $this->sampleLimit));
    }

    public function analyze(array $vehicleLoadReport, array $shippers, array $cnacs): array
    {
        $sources = [
            'vehicle_load_report' => $vehicleLoadReport,
            'shippers' => $shippers,
            'cnacs' => $cnacs,
        ];
        $union = [];
        foreach ($sources as $source) {
            foreach (array_keys($source['vins']) as $vin) {
                $union[$vin] = true;
            }
        }

        $categoryVins = array_fill_keys(array_values(self::CATEGORY_BY_MASK), []);
        $units = [];
        foreach (array_keys($union) as $vin) {
            $presence = [
                'vehicle_load_report' => isset($vehicleLoadReport['vins'][$vin]),
                'shippers' => isset($shippers['vins'][$vin]),
                'cnacs' => isset($cnacs['vins'][$vin]),
            ];
            $mask = ($presence['vehicle_load_report'] ? 1 : 0)
                | ($presence['shippers'] ? 2 : 0)
                | ($presence['cnacs'] ? 4 : 0);
            $category = self::CATEGORY_BY_MASK[$mask];
            $categoryVins[$category][$vin] = true;
            $duplicates = [
                'vehicle_load_report' => (int) ($vehicleLoadReport['duplicates'][$vin] ?? 0),
                'shippers' => (int) ($shippers['duplicates'][$vin] ?? 0),
                'cnacs' => (int) ($cnacs['duplicates'][$vin] ?? 0),
            ];
            $isDuplicate = array_sum($duplicates) > 0;
            $hasBlockingDuplicate = $duplicates['vehicle_load_report'] > 0
                || $duplicates['shippers'] > 0;
            $missing = array_keys(array_filter($presence, static fn (bool $present): bool => !$present));
            $unit = [
                'vin' => $vin,
                'presence' => $presence,
                'category' => $category,
                'status' => $hasBlockingDuplicate ? 'duplicado' : ($mask === 7 ? 'completo' : 'faltante'),
                'duplicate' => $isDuplicate,
                'duplicate_counts' => $duplicates,
                'inconsistent' => false,
                'blocking_inconsistency' => false,
                'eligible' => $mask === 7 && !$hasBlockingDuplicate,
                'missing_sources' => $missing,
                'observations' => $hasBlockingDuplicate
                    ? 'VIN duplicado dentro de Vehicle Load o Shippers.'
                    : ($duplicates['cnacs'] > 0
                        ? 'CNACS contiene filas adicionales; se conserva la primera coincidencia.'
                        : ($missing === [] ? '' : 'Falta en: ' . implode(', ', $missing) . '.')),
                'source_data' => [
                    'vehicle_load_report' => $vehicleLoadReport['records'][$vin]['data'] ?? [],
                    'shippers' => $shippers['records'][$vin]['data'] ?? [],
                    'cnacs' => $cnacs['records'][$vin]['data'] ?? [],
                    'cnacs_additional' => array_values(array_slice(
                        $cnacs['record_occurrences'][$vin] ?? [],
                        1,
                    )),
                ],
                'source_rows' => [
                    'vehicle_load_report' => $vehicleLoadReport['records'][$vin]['row'] ?? null,
                    'shippers' => $shippers['records'][$vin]['row'] ?? null,
                    'cnacs' => $cnacs['records'][$vin]['row'] ?? null,
                ],
            ];
            $units[] = $unit;
            foreach (array_slice((array) ($vehicleLoadReport['record_occurrences'][$vin] ?? []), 1) as $occurrence) {
                $repeatedUnit = $unit;
                $repeatedUnit['source_data']['vehicle_load_report'] = (array) ($occurrence['data'] ?? []);
                $repeatedUnit['source_rows']['vehicle_load_report'] = (int) ($occurrence['row'] ?? 0);
                $repeatedUnit['observations'] = 'Ocurrencia adicional conservada desde Vehicle Load.';
                $units[] = $repeatedUnit;
            }
        }
        usort($units, static fn (array $left, array $right): int => strcmp($left['vin'], $right['vin']));

        $categories = [];
        foreach ($categoryVins as $key => $vins) {
            $categories[$key] = $this->summarizeSet($vins);
        }
        foreach ($sources as $key => $source) {
            $categories['duplicates_' . $key] = $this->summarizeSet(array_fill_keys(array_keys($source['duplicates']), true));
        }

        $fileSummary = [];
        foreach ($sources as $key => $source) {
            $fileSummary[$key] = [
                'label' => $source['label'],
                'valid_records' => $source['valid_records'],
                'unique_vins' => $source['unique_count'],
                'duplicates' => $source['duplicate_count'],
                'empty_vin' => $source['empty_vin'],
            ];
        }

        $presentInAll = $categories['present_in_all']['count'];
        $duplicateTotal = array_sum(array_column($fileSummary, 'duplicates'));
        $emptyTotal = array_sum(array_column($fileSummary, 'empty_vin'));

        return [
            'analyzed_at' => gmdate('c'),
            'files' => $fileSummary,
            'categories' => $categories,
            'consistency' => [
                'total_unique_combined' => count($union),
                'total_present_in_all' => $presentInAll,
                'total_with_any_absence' => count($union) - $presentInAll,
                'total_internal_duplicates' => $duplicateTotal,
                'total_empty_vins' => $emptyTotal,
                'missing_vehicle_load_report' => count($union) - (int) $fileSummary['vehicle_load_report']['unique_vins'],
                'missing_shippers' => count($union) - (int) $fileSummary['shippers']['unique_vins'],
                'missing_cnacs' => count($union) - (int) $fileSummary['cnacs']['unique_vins'],
                'total_inconsistencies' => 0,
                'total_consist_candidates' => count(array_filter($units, static fn (array $unit): bool => $unit['eligible'])),
            ],
            'units' => $units,
        ];
    }

    private function summarizeSet(array $set): array
    {
        $vins = array_keys($set);
        sort($vins, SORT_STRING);
        $count = count($vins);
        $sample = array_slice($vins, 0, max(0, $this->sampleLimit));
        return ['count' => $count, 'sample' => $sample, 'remaining' => max(0, $count - count($sample))];
    }
}
