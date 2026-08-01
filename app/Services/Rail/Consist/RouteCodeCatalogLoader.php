<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use App\Repositories\Rail\RouteCodeCatalogRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

final class RouteCodeCatalogLoader
{
    private const EXPECTED_SHA256 = '42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e';

    public function __construct(
        private string|RouteCodeCatalogRepository $source,
        private ?string $expectedSha256 = self::EXPECTED_SHA256,
    )
    {
    }

    public function load(): array
    {
        if ($this->source instanceof RouteCodeCatalogRepository) {
            return $this->source->activeCatalog()
                ?? throw new RuntimeException(
                    'No existe un catálogo maestro de rutas activo. Verifique que la migración oficial del catálogo esté aplicada.',
                );
        }
        $path = $this->source;
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('El catálogo maestro de rutas no está disponible.');
        }
        $sha256 = hash_file('sha256', $path);
        if ($this->expectedSha256 !== null && !hash_equals($this->expectedSha256, $sha256)) {
            throw new RuntimeException('El catálogo maestro no corresponde a la versión aprobada.');
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['route_codes']);
        $spreadsheet = $reader->load($path);
        try {
            $sheet = $spreadsheet->getSheetByName('route_codes');
            if ($sheet === null) {
                throw new RuntimeException('La hoja route_codes no existe en el catálogo.');
            }
            $headers = [];
            for ($column = 1; $column <= 11; $column++) {
                $headers[$column] = trim((string) $sheet->getCell([$column, 1])->getFormattedValue());
            }
            if (($headers[1] ?? '') !== 'Route Code'
                || ($headers[3] ?? '') !== 'Market'
                || ($headers[4] ?? '') !== 'Shipping Destination'
            ) {
                throw new RuntimeException('El esquema de route_codes no coincide con el contrato aprobado.');
            }

            $routes = [];
            $records = [];
            $duplicates = [];
            for ($row = 2, $last = $sheet->getHighestDataRow(); $row <= $last; $row++) {
                $routeCode = $this->normalize($sheet->getCell([1, $row])->getFormattedValue());
                if ($routeCode === '') {
                    continue;
                }
                $record = [
                    'source_row' => $row,
                    'route_code' => $routeCode,
                    'route_king' => trim((string) $sheet->getCell([2, $row])->getFormattedValue()),
                    'market' => trim((string) $sheet->getCell([3, $row])->getFormattedValue()),
                    'shipping_destination' => trim((string) $sheet->getCell([4, $row])->getFormattedValue()),
                    'carrier' => trim((string) $sheet->getCell([5, $row])->getFormattedValue()),
                    'heavy_duty' => trim((string) $sheet->getCell([6, $row])->getFormattedValue()),
                    'usa_canada' => trim((string) $sheet->getCell([7, $row])->getFormattedValue()),
                    'production_plant' => trim((string) $sheet->getCell([8, $row])->getFormattedValue()),
                    'rc_plant' => trim((string) $sheet->getCell([9, $row])->getFormattedValue()),
                    'load_by' => trim((string) $sheet->getCell([10, $row])->getFormattedValue()),
                    'border_crossing' => trim((string) $sheet->getCell([11, $row])->getFormattedValue()),
                ];
                $records[] = $record;
                if (isset($routes[$routeCode])) {
                    $duplicates[$routeCode][] = $record;
                    continue;
                }
                $routes[$routeCode] = $record;
            }

            return [
                'version' => [
                    'source_filename' => basename($path),
                    'source_sha256' => $sha256,
                    'sheet' => 'route_codes',
                    'record_count' => count($routes) + array_sum(array_map('count', $duplicates)),
                ],
                'routes' => $routes,
                'records' => $records,
                'route_variants' => array_reduce($records, static function (array $groups, array $record): array {
                    $groups[$record['route_code']][] = $record;
                    return $groups;
                }, []),
                'duplicate_routes' => $duplicates,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function normalize(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
