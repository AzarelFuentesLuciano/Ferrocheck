<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use DomainException;

final class RouteCodeResolver
{
    public function resolve(
        array $catalog,
        string $routeCode,
        string $vehicleCarrier,
        string $vehicleDestination = '',
    ): array {
        $code = $this->normalize($routeCode);
        $variants = array_values((array) (($catalog['route_variants'][$code] ?? null)
            ?? (isset($catalog['routes'][$code]) ? [$catalog['routes'][$code]] : [])));
        usort($variants, static fn (array $left, array $right): int =>
            [(int) ($left['source_row'] ?? PHP_INT_MAX), (int) ($left['id'] ?? PHP_INT_MAX)]
            <=> [(int) ($right['source_row'] ?? PHP_INT_MAX), (int) ($right['id'] ?? PHP_INT_MAX)]
        );

        if ($variants === []) {
            throw new DomainException(sprintf(
                'El Route Code "%s" no existe en el catálogo activo. Corrija el lote o el catálogo antes de generar el Consist.',
                $routeCode,
            ));
        }

        $outputs = $this->distinctOutputs($variants);
        if (count($variants) === 1) {
            return $this->result($code, $variants, $variants[0], 'unique');
        }
        if (count($outputs) === 1) {
            return $this->result($code, $variants, $outputs[0], 'equivalent_duplicates');
        }

        if ($this->normalize($vehicleCarrier) === 'KCSM') {
            return $this->result($code, $variants, $variants[0], 'kcsm_override', 'Laredo');
        }

        $destinationMatches = $this->matchingDestinationOutputs($outputs, $vehicleDestination);
        if (count($destinationMatches) === 1) {
            return $this->result($code, $variants, $destinationMatches[0], 'destination_match');
        }

        $selected = $variants[0];
        $warning = [
            'type' => 'route_code_historical_fallback',
            'route_code' => $code,
            'variant_count' => count($variants),
            'criteria' => [
                'carrier' => 'only_kcsm_override',
                'destination_location' => trim($vehicleDestination) === '' ? 'not_available' : 'no_unique_match',
                'fallback' => 'source_row_asc',
            ],
            'selected_source_row' => (int) ($selected['source_row'] ?? 0),
            'selected_destination' => trim((string) ($selected['shipping_destination'] ?? '')),
            'variants' => $this->summaries($variants),
            'message' => sprintf(
                'Route Code %s conservó la primera coincidencia histórica porque sus %d variantes no pudieron desambiguarse.',
                $code,
                count($variants),
            ),
        ];

        return $this->result($code, $variants, $selected, 'historical_first_match', null, $warning);
    }

    private function distinctOutputs(array $variants): array
    {
        $outputs = [];
        foreach ($variants as $variant) {
            $key = $this->normalize($variant['market'] ?? '')
                . '|' . $this->normalize($variant['shipping_destination'] ?? '');
            $outputs[$key] ??= $variant;
        }
        return array_values($outputs);
    }

    private function matchingDestinationOutputs(array $outputs, string $vehicleDestination): array
    {
        $vehicle = $this->normalizeLocation($vehicleDestination);
        if ($vehicle === '') {
            return [];
        }
        return array_values(array_filter($outputs, function (array $variant) use ($vehicle): bool {
            $catalogDestination = $this->normalizeLocation($variant['shipping_destination'] ?? '');
            if ($catalogDestination === '' || mb_strlen($catalogDestination, 'UTF-8') < 3) {
                return false;
            }
            return $vehicle === $catalogDestination
                || str_contains(' ' . $vehicle . ' ', ' ' . $catalogDestination . ' ');
        }));
    }

    private function result(
        string $code,
        array $variants,
        array $selected,
        string $mode,
        ?string $destinationOverride = null,
        ?array $warning = null,
    ): array {
        $destination = $destinationOverride ?? trim((string) ($selected['shipping_destination'] ?? ''));
        return [
            'route_code' => $code,
            'selected_row' => $selected,
            'market' => trim((string) ($selected['market'] ?? '')),
            'shipping_destination' => $destination,
            'resolved_destination' => $destination,
            'resolution_mode' => $mode,
            'variant_count' => count($variants),
            'selected_source_row' => (int) ($selected['source_row'] ?? 0),
            'warning' => $warning,
            'variants' => $this->summaries($variants),
        ];
    }

    private function summaries(array $variants): array
    {
        return array_map(static fn (array $variant): array => [
            'source_row' => (int) ($variant['source_row'] ?? 0),
            'market' => trim((string) ($variant['market'] ?? '')),
            'shipping_destination' => trim((string) ($variant['shipping_destination'] ?? '')),
        ], $variants);
    }

    private function normalizeLocation(mixed $value): string
    {
        $normalized = $this->normalize($value);
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? '');
    }

    private function normalize(mixed $value): string
    {
        return mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '',
            'UTF-8',
        );
    }
}
