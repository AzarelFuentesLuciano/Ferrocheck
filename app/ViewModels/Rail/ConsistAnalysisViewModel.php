<?php

declare(strict_types=1);

namespace App\ViewModels\Rail;

final readonly class ConsistAnalysisViewModel
{
    public array $summary;
    public array $units;
    public array $eligibleVins;
    public ?array $detail;
    public array $pagination;
    public array $filters;

    public function __construct(public array $result, array $query = [])
    {
        $consistency = (array) ($result['consistency'] ?? []);
        $files = (array) ($result['files'] ?? []);
        $this->summary = [
            'vehicle_load_report' => (int) ($files['vehicle_load_report']['unique_vins'] ?? 0),
            'shippers' => (int) ($files['shippers']['unique_vins'] ?? 0),
            'cnacs' => (int) ($files['cnacs']['unique_vins'] ?? 0),
            'unique' => (int) ($consistency['total_unique_combined'] ?? 0),
            'complete' => (int) ($consistency['total_present_in_all'] ?? 0),
            'missing_vehicle_load_report' => (int) ($consistency['missing_vehicle_load_report'] ?? 0),
            'missing_shippers' => (int) ($consistency['missing_shippers'] ?? 0),
            'missing_cnacs' => (int) ($consistency['missing_cnacs'] ?? 0),
            'duplicates' => (int) ($consistency['total_internal_duplicates'] ?? 0),
            'inconsistencies' => (int) ($consistency['total_inconsistencies'] ?? 0),
            'candidates' => (int) ($consistency['total_consist_candidates'] ?? 0),
        ];
        $search = mb_strtoupper(trim((string) ($query['vin_buscar'] ?? '')), 'UTF-8');
        $status = trim((string) ($query['estado_cruce'] ?? ''));
        $duplicates = trim((string) ($query['duplicados'] ?? ''));
        $filtered = array_values(array_filter((array) ($result['units'] ?? []), static function (array $unit) use ($search, $status, $duplicates): bool {
            if ($search !== '' && !str_contains((string) ($unit['vin'] ?? ''), $search)) {
                return false;
            }
            if ($status !== '' && ($unit['status'] ?? '') !== $status) {
                return false;
            }
            if ($duplicates === 'si' && ($unit['duplicate'] ?? false) !== true) {
                return false;
            }
            if ($duplicates === 'no' && ($unit['duplicate'] ?? false) === true) {
                return false;
            }
            return true;
        }));
        $page = max(1, (int) ($query['pagina'] ?? 1));
        $perPage = 25;
        $total = count($filtered);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $this->units = array_slice($filtered, ($page - 1) * $perPage, $perPage);
        $this->eligibleVins = array_values(array_map(
            static fn (array $unit): string => (string) $unit['vin'],
            array_filter((array) ($result['units'] ?? []), static fn (array $unit): bool => ($unit['eligible'] ?? false) === true),
        ));
        $detailVin = mb_strtoupper(trim((string) ($query['vin_detalle'] ?? '')), 'UTF-8');
        $details = array_values(array_filter(
            (array) ($result['units'] ?? []),
            static fn (array $unit): bool => ($unit['vin'] ?? '') === $detailVin,
        ));
        $this->detail = $details[0] ?? null;
        $this->pagination = ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => $perPage];
        $this->filters = ['vin_buscar' => $search, 'estado_cruce' => $status, 'duplicados' => $duplicates];
    }
}
