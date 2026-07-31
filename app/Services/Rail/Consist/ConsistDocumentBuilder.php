<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use DomainException;

final class ConsistDocumentBuilder
{
    public const HEADERS = [
        'fdTransportationName1', 'fdwholevin', 'Route Code', 'Carrier',
        'fdTrack', 'Shipper', 'Upfit Cost', 'fdDestinationLocation', 'Cruce',
        'CNACS-Pedimento', 'fdManufacturerRouteCode2', 'fdLoadID', 'fdCustom1', 'fdCustom2',
    ];

    public function __construct(
        private array $referenceOrder = [],
        private ?ConsistOperationalSummary $operationalSummary = null,
    ) {
    }

    public function build(
        string $token,
        array $analysis,
        array $catalog,
        int $userId,
        ConsistOperationalPeriod $period,
    ): ConsistDraftResult
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new DomainException('El token de análisis no es válido.');
        }
        if (!isset($analysis['units']) || !is_array($analysis['units'])) {
            throw new DomainException('El análisis de origen no contiene unidades.');
        }
        $routes = (array) ($catalog['routes'] ?? []);
        $issues = [];
        $units = [];
        $inputPosition = 0;
        foreach ($analysis['units'] as $sourceUnit) {
            $inputPosition++;
            $vin = $this->normalize($sourceUnit['vin'] ?? '');
            if ($vin === '' || ($sourceUnit['presence']['vehicle_load_report'] ?? false) !== true) {
                throw new DomainException('El análisis contiene un VIN ajeno a Vehicle Load.');
            }
            if (($sourceUnit['presence']['shippers'] ?? false) !== true
                || ($sourceUnit['presence']['cnacs'] ?? false) !== true
            ) {
                $issues[] = ['vin' => $vin, 'type' => 'missing_source'];
                continue;
            }
            $source = (array) ($sourceUnit['source_data'] ?? []);
            $vehicle = (array) ($source['vehicle_load_report'] ?? []);
            $shippers = (array) ($source['shippers'] ?? []);
            $cnacs = (array) ($source['cnacs'] ?? []);
            $routeCode = trim((string) ($vehicle['fdmanufacturerroutecode'] ?? ''));
            $route = $routes[$this->normalize($routeCode)] ?? null;
            $market = is_array($route) && trim((string) ($route['market'] ?? '')) !== ''
                ? (string) $route['market']
                : 'REVISAR';
            $carrier = trim((string) ($vehicle['fdSCAC'] ?? ''));
            $destination = $carrier === 'KCSM'
                ? 'Laredo'
                : (is_array($route) && trim((string) ($route['shipping_destination'] ?? '')) !== ''
                    ? (string) $route['shipping_destination']
                    : 'REVISAR');
            $shipper = trim((string) ($shippers['Textbox7'] ?? '')) ?: 'Pending';
            $pedimento = trim((string) ($cnacs['Pedimento'] ?? ''));
            if ($pedimento === '') {
                $pedimento = $cnacs === [] ? 'Falta agregar este VIN en hoja de CNACS' : 'Pending';
            }
            $final = [
                trim((string) ($vehicle['fdTransportationName1'] ?? '')),
                $vin,
                $routeCode,
                $carrier,
                trim((string) ($vehicle['fdTrack'] ?? '')),
                $shipper,
                '',
                trim((string) ($vehicle['fdDestinationLocation'] ?? '')),
                $destination,
                $pedimento,
                trim((string) ($vehicle['fdManufacturerRouteCode2'] ?? '')),
                trim((string) ($vehicle['fdLoadID'] ?? '')),
                trim((string) ($vehicle['fdCustom1'] ?? '')),
                trim((string) ($vehicle['fdCustom2'] ?? '')),
            ];
            $unitIssues = [];
            if ($route === null) {
                $unitIssues[] = 'route_not_found';
            }
            $units[] = [
                'stable_input_position' => (int) ($sourceUnit['source_rows']['vehicle_load_report'] ?? $inputPosition),
                'vin' => $vin,
                'track' => $final[4],
                'route_code' => $routeCode,
                'market' => $market,
                'shipping_destination' => $destination,
                'final_columns' => array_combine(self::HEADERS, $final),
                'source_data' => [
                    'vehicle_load_report' => $vehicle,
                    'shippers' => $shippers,
                    'cnacs_selected' => $cnacs,
                    'cnacs_additional' => (array) ($source['cnacs_additional'] ?? []),
                    'cnacs_additional_count' => (int) ($sourceUnit['duplicate_counts']['cnacs'] ?? 0),
                ],
                'trace' => $this->trace(),
                'issues' => $unitIssues,
            ];
        }

        usort($units, function (array $left, array $right): int {
            $track = strcmp($left['track'], $right['track']);
            if ($track !== 0) {
                return $track;
            }
            $leftReference = $this->referenceOrder[$left['vin']] ?? null;
            $rightReference = $this->referenceOrder[$right['vin']] ?? null;
            if (is_int($leftReference) && is_int($rightReference)) {
                return $leftReference <=> $rightReference;
            }
            return $left['stable_input_position'] <=> $right['stable_input_position'];
        });
        $platforms = [];
        foreach ($units as $position => &$unit) {
            $global = $position + 1;
            $platformPosition = intdiv($position, 8) + 1;
            $unit['global_position'] = $global;
            $unit['platform_position'] = (($position % 8) + 1);
            $unit['platform_number'] = (string) $unit['final_columns']['fdTransportationName1'];
            $platforms[$platformPosition] ??= [
                'position' => $platformPosition,
                'platform_number' => $unit['platform_number'],
                'track' => $unit['track'],
                'total_units' => 0,
                'unit_vins' => [],
            ];
            $platforms[$platformPosition]['total_units']++;
            $platforms[$platformPosition]['unit_vins'][] = $unit['vin'];
        }
        unset($unit);
        $operationalSummary = ($this->operationalSummary ?? new ConsistOperationalSummary())
            ->fromFinalUnits((array) $analysis['units'], $units);
        foreach ($operationalSummary['inconsistencies'] as $inconsistency) {
            $issues[] = $inconsistency;
        }

        foreach ($platforms as $platform) {
            if ($platform['total_units'] > 8) {
                $issues[] = ['platform' => $platform['platform_number'], 'type' => 'platform_size', 'count' => $platform['total_units']];
            }
        }
        return new ConsistDraftResult(
            $token,
            gmdate('c'),
            $userId,
            $period->start->format('Y-m-d'),
            $period->end->format('Y-m-d'),
            'borrador',
            (array) ($catalog['version'] ?? []),
            array_values($platforms),
            $units,
            $issues,
            array_sum(array_map(
                static fn (array $unit): int => (int) ($unit['duplicate_counts']['cnacs'] ?? 0),
                $analysis['units'],
            )),
            $operationalSummary,
        );
    }

    private function normalize(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private function trace(): array
    {
        return [
            'fdTransportationName1' => 'vehicle_load_report.fdTransportationName1',
            'fdwholevin' => 'vehicle_load_report.fdwholevin',
            'Route Code' => 'vehicle_load_report.fdmanufacturerroutecode',
            'Carrier' => 'vehicle_load_report.fdSCAC',
            'Bloque' => 'route_codes.Market (etiqueta histórica)',
            'fdTrack' => 'vehicle_load_report.fdTrack',
            'Shipper' => 'shippers.Textbox7',
            'Upfit Cost' => 'fallback vacío comprobado',
            'fdDestinationLocation' => 'vehicle_load_report.fdDestinationLocation',
            'Cruce' => 'route_codes.Shipping Destination (etiqueta histórica)',
            'CNACS-Pedimento' => 'primera coincidencia cnacs.Pedimento',
            'fdManufacturerRouteCode2' => 'vehicle_load_report.fdManufacturerRouteCode2',
            'fdLoadID' => 'vehicle_load_report.fdLoadID',
            'fdCustom1' => 'vehicle_load_report.fdCustom1',
            'fdCustom2' => 'vehicle_load_report.fdCustom2',
        ];
    }
}
