<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use DomainException;

final class ConsistDraftGenerator
{
    public function generate(
        string $analysisToken,
        array $analysis,
        array $selectedVins,
        int $userId,
        array $metadata,
    ): array {
        if (!preg_match('/^[a-f0-9]{64}$/', $analysisToken)) {
            throw new DomainException('El análisis de origen no es válido.');
        }
        $unitsByVin = [];
        foreach ($analysis['units'] ?? [] as $unit) {
            $vin = (string) ($unit['vin'] ?? '');
            if ($vin !== '') {
                $unitsByVin[$vin] = $unit;
            }
        }
        $selected = array_values(array_unique(array_filter(array_map(
            static fn (mixed $vin): string => mb_strtoupper(trim((string) $vin), 'UTF-8'),
            $selectedVins,
        ))));
        if ($selected === []) {
            $selected = array_keys(array_filter(
                $unitsByVin,
                static fn (array $unit): bool => ($unit['eligible'] ?? false) === true,
            ));
        }
        if ($selected === []) {
            throw new DomainException('No existen VIN elegibles para generar el borrador.');
        }

        $units = [];
        foreach ($selected as $position => $vin) {
            if (!isset($unitsByVin[$vin])) {
                throw new DomainException(sprintf('El VIN %s no pertenece al análisis.', $vin));
            }
            $unit = $unitsByVin[$vin];
            if (($unit['eligible'] ?? false) !== true) {
                throw new DomainException(sprintf('El VIN %s requiere revisión y no puede incluirse automáticamente.', $vin));
            }
            $units[] = [
                'position' => $position + 1,
                'vin' => $vin,
                'cross_status' => (string) ($unit['status'] ?? 'completo'),
                'manually_included' => false,
                'observations' => trim((string) ($unit['observations'] ?? '')),
                'source_data' => (array) ($unit['source_data'] ?? []),
            ];
        }

        $date = trim((string) ($metadata['fecha_consist'] ?? ''));
        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            throw new DomainException('La fecha del Consist no es válida.');
        }

        return [
            'analysis_token' => $analysisToken,
            'date' => $date,
            'description' => $this->optional($metadata['descripcion'] ?? null, 500),
            'observations' => $this->optional($metadata['observaciones'] ?? null, 5000),
            'created_by' => $userId,
            'total_units' => count($units),
            'units' => $units,
        ];
    }

    private function optional(mixed $value, int $maxLength): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }
        if (mb_strlen($normalized) > $maxLength) {
            throw new DomainException('Uno de los textos supera la longitud permitida.');
        }
        return $normalized;
    }
}
