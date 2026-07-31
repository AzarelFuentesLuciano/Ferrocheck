<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final class ConsistOperationalSummary
{
    public const PLATFORM_HEADER = 'fdTransportationName1';
    public const PEDIMENTO_HEADER = 'CNACS-Pedimento';

    private const PLATFORM_ALIASES = [
        'FDTRANSPORTATIONNAME1',
        'FD TRANSPORTATION NAME 1',
        'TRANSPORTATION NAME 1',
        'PLATAFORMA',
        'NUMERO DE PLATAFORMA',
        'NÚMERO DE PLATAFORMA',
    ];

    private const PENDING_VALUES = [
        'PENDING',
        'FALTA AGREGAR ESTE VIN EN HOJA DE CNACS',
    ];

    public function fromAnalysis(array $analysis): array
    {
        $loaded = [];
        $pending = [];
        foreach ((array) ($analysis['units'] ?? []) as $unit) {
            $source = (array) ($unit['source_data'] ?? []);
            $vehicle = (array) ($source['vehicle_load_report'] ?? []);
            $platform = $this->platformFromRow($vehicle);
            if ($platform !== '') {
                $loaded[$platform] = true;
            }
            if (($unit['presence']['shippers'] ?? false) !== true
                || ($unit['presence']['cnacs'] ?? false) !== true
                || $platform === ''
            ) {
                continue;
            }
            $cnacs = (array) ($source['cnacs'] ?? []);
            if ($this->isPending($cnacs['Pedimento'] ?? '')) {
                $pending[$platform] = true;
            }
        }

        return $this->result(array_keys($loaded), array_keys($pending));
    }

    public function fromFinalUnits(array $sourceUnits, array $finalUnits): array
    {
        $loaded = [];
        foreach ($sourceUnits as $unit) {
            $vehicle = (array) (($unit['source_data']['vehicle_load_report'] ?? null)
                ?? ($unit['vehicle_load_data_json'] ?? []));
            $platform = $this->platformFromRow($vehicle);
            if ($platform !== '') {
                $loaded[$platform] = true;
            }
        }

        $pending = [];
        foreach ($finalUnits as $unit) {
            $row = (array) (($unit['final_columns'] ?? null) ?? ($unit['final_data_json'] ?? []));
            $platform = $this->normalizePlatform($row[self::PLATFORM_HEADER] ?? '');
            if ($platform !== '' && $this->isPending($row[self::PEDIMENTO_HEADER] ?? '')) {
                $pending[$platform] = true;
            }
        }

        return $this->result(array_keys($loaded), array_keys($pending));
    }

    public function pendingValues(): array
    {
        return self::PENDING_VALUES;
    }

    private function result(array $loadedPlatforms, array $pendingPlatforms): array
    {
        $total = count($loadedPlatforms);
        $pending = count($pendingPlatforms);
        $consistent = $pending <= $total;

        return [
            'total_loaded_platforms' => $total,
            'pending_platforms' => $pending,
            'confirmed_platforms' => max(0, $total - $pending),
            'is_consistent' => $consistent,
            'inconsistencies' => $consistent ? [] : [[
                'type' => 'pending_platforms_exceed_total',
                'total_loaded_platforms' => $total,
                'pending_platforms' => $pending,
            ]],
            'platform_header' => self::PLATFORM_HEADER,
            'platform_aliases' => self::PLATFORM_ALIASES,
            'pedimento_header' => self::PEDIMENTO_HEADER,
            'pending_values' => self::PENDING_VALUES,
            'empty_pedimento_is_pending' => true,
            'loaded_platforms' => $loadedPlatforms,
            'pending_platform_numbers' => $pendingPlatforms,
        ];
    }

    private function platformFromRow(array $row): string
    {
        foreach ($row as $header => $value) {
            if (in_array($this->normalizeHeader((string) $header), self::PLATFORM_ALIASES, true)) {
                return $this->normalizePlatform($value);
            }
        }
        return '';
    }

    private function isPending(mixed $value): bool
    {
        $normalized = $this->normalizeText($value);
        return $normalized === '' || in_array($normalized, self::PENDING_VALUES, true);
    }

    private function normalizePlatform(mixed $value): string
    {
        return $this->normalizeText($value);
    }

    private function normalizeHeader(string $value): string
    {
        return $this->normalizeText(str_replace(['_', '-'], ' ', $value));
    }

    private function normalizeText(mixed $value): string
    {
        return mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '',
            'UTF-8',
        );
    }
}
