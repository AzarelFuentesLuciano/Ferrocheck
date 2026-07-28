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
        foreach (array_keys($union) as $vin) {
            $mask = (isset($vehicleLoadReport['vins'][$vin]) ? 1 : 0)
                | (isset($shippers['vins'][$vin]) ? 2 : 0)
                | (isset($cnacs['vins'][$vin]) ? 4 : 0);
            $categoryVins[self::CATEGORY_BY_MASK[$mask]][$vin] = true;
        }

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
            ],
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
