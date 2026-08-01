<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
final class OfficialRouteCatalogSeedGenerator
{
    public const SOURCE_FILENAME = 'vascor_sm_db.xlsx';
    public const SOURCE_SHA256 = '42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e';
    public const MIGRATION = '20260731_019_seed_official_rail_route_catalog';
    public const HEADERS = [
        'Route Code', 'Route King', 'Market', 'Shipping Destination', 'Carrier',
        'Heavy Duty', 'USA/Canada', 'Production Plant', 'RC-Plant', 'Load by',
        'Border crossing',
    ];

    public function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('El XLSX oficial no está disponible.');
        }
        $sha256 = hash_file('sha256', $path);
        if (!hash_equals(self::SOURCE_SHA256, $sha256)) {
            throw new RuntimeException('El SHA-256 del XLSX no coincide con la fuente oficial.');
        }
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['route_codes']);
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheetByName('route_codes')
                ?? throw new RuntimeException('La hoja route_codes no existe.');
            $headers = [];
            foreach (self::HEADERS as $index => $expected) {
                $headers[] = trim((string) $sheet->getCell([$index + 1, 1])->getFormattedValue());
            }
            if ($headers !== self::HEADERS) {
                throw new RuntimeException('Los encabezados de route_codes no coinciden con el contrato oficial.');
            }
            $rows = [];
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $values = [];
                foreach (self::HEADERS as $index => $header) {
                    $values[$header] = $this->normalize(
                        $sheet->getCell([$index + 1, $row])->getFormattedValue(),
                        $header === 'Route Code',
                    );
                }
                if (implode('', $values) === '') {
                    continue;
                }
                if ($values['Route Code'] === '') {
                    throw new RuntimeException(sprintf('La fila %d no contiene Route Code.', $row));
                }
                $rows[] = ['source_row' => $row] + $values;
            }
            if (count($rows) !== 436) {
                throw new RuntimeException(sprintf('Se esperaban 436 filas oficiales; se detectaron %d.', count($rows)));
            }
            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function migration(array $rows): string
    {
        $values = array_map(fn (array $row): string => sprintf(
            "(@official_catalog_version_id,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,'%s')",
            $row['source_row'],
            $this->sql($row['Route Code'], false),
            $this->sql($row['Route King']),
            $this->sql($row['Market']),
            $this->sql($row['Shipping Destination']),
            $this->sql($row['Carrier']),
            $this->sql($row['Heavy Duty']),
            $this->sql($row['USA/Canada']),
            $this->sql($row['Production Plant']),
            $this->sql($row['RC-Plant']),
            $this->sql($row['Load by']),
            $this->sql($row['Border crossing']),
            self::MIGRATION,
        ), $rows);

        return implode("\n", [
            '-- Generado de forma determinista desde route_codes; no editar las filas manualmente.',
            '-- Fuente SHA-256: ' . self::SOURCE_SHA256,
            '-- MariaDB: los ALTER TABLE producen commits implícitos; el bloque DML posterior sí es transaccional.',
            '',
            'ALTER TABLE rail_catalog_versions',
            '    MODIFY imported_by BIGINT UNSIGNED NULL,',
            "    ADD COLUMN IF NOT EXISTS origin VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER active,",
            '    ADD COLUMN IF NOT EXISTS seed_migration VARCHAR(64) NULL AFTER origin,',
            "    ADD CONSTRAINT IF NOT EXISTS chk_rail_catalog_system_importer CHECK (imported_by IS NOT NULL OR origin='system');",
            '',
            'ALTER TABLE rail_catalog_route_codes',
            '    DROP INDEX IF EXISTS uq_rail_catalog_route_version_code,',
            '    ADD COLUMN IF NOT EXISTS heavy_duty VARCHAR(255) NULL AFTER carrier,',
            '    ADD COLUMN IF NOT EXISTS usa_canada VARCHAR(255) NULL AFTER heavy_duty,',
            '    ADD COLUMN IF NOT EXISTS production_plant VARCHAR(255) NULL AFTER usa_canada,',
            '    ADD COLUMN IF NOT EXISTS rc_plant VARCHAR(255) NULL AFTER production_plant,',
            '    ADD COLUMN IF NOT EXISTS load_by VARCHAR(255) NULL AFTER rc_plant,',
            '    ADD COLUMN IF NOT EXISTS border_crossing VARCHAR(255) NULL AFTER load_by,',
            '    ADD COLUMN IF NOT EXISTS seed_migration VARCHAR(64) NULL AFTER border_crossing,',
            '    ADD UNIQUE INDEX IF NOT EXISTS uq_rail_catalog_route_version_source (catalog_version_id,source_row);',
            '',
            'START TRANSACTION;',
            "SET @official_sha256 := '" . self::SOURCE_SHA256 . "';",
            'SET @official_preexisting := (SELECT COUNT(*) FROM rail_catalog_versions WHERE source_sha256=@official_sha256);',
            '',
            'INSERT INTO rail_catalog_versions',
            '    (source_filename,source_sha256,imported_by,active,origin,seed_migration)',
            "VALUES ('" . self::SOURCE_FILENAME . "',@official_sha256,NULL,0,'system',NULL)",
            'ON DUPLICATE KEY UPDATE',
            "    source_filename=VALUES(source_filename),origin='system',id=LAST_INSERT_ID(id);",
            '',
            'SET @official_catalog_version_id := (SELECT id FROM rail_catalog_versions WHERE source_sha256=@official_sha256 LIMIT 1);',
            'UPDATE rail_catalog_versions',
            "SET seed_migration=IF(@official_preexisting=0,CONCAT('" . self::MIGRATION . "',':created'),CONCAT('" . self::MIGRATION . "',':adopted'))",
            'WHERE id=@official_catalog_version_id;',
            'UPDATE rail_catalog_versions SET active=0 WHERE active=1 AND id<>@official_catalog_version_id;',
            'UPDATE rail_catalog_versions SET active=1 WHERE id=@official_catalog_version_id;',
            '',
            'INSERT INTO rail_catalog_route_codes',
            '    (catalog_version_id,source_row,route_code,route_king,market,shipping_destination,carrier,',
            '     heavy_duty,usa_canada,production_plant,rc_plant,load_by,border_crossing,seed_migration)',
            'VALUES',
            implode(",\n", $values),
            'ON DUPLICATE KEY UPDATE',
            '    route_code=VALUES(route_code),route_king=VALUES(route_king),market=VALUES(market),',
            '    shipping_destination=VALUES(shipping_destination),carrier=VALUES(carrier),',
            '    heavy_duty=VALUES(heavy_duty),usa_canada=VALUES(usa_canada),',
            '    production_plant=VALUES(production_plant),rc_plant=VALUES(rc_plant),',
            '    load_by=VALUES(load_by),border_crossing=VALUES(border_crossing);',
            '',
            'COMMIT;',
            '',
        ]);
    }

    private function normalize(mixed $value, bool $uppercase = false): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        return $uppercase ? mb_strtoupper($value, 'UTF-8') : $value;
    }

    private function sql(string $value, bool $emptyAsNull = true): string
    {
        if ($emptyAsNull && $value === '') {
            return 'NULL';
        }
        return "'" . str_replace("'", "''", $value) . "'";
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $root = dirname(__DIR__, 2);
    $source = $root . '/docs/rail/consist/referencias/' . OfficialRouteCatalogSeedGenerator::SOURCE_FILENAME;
    $output = $root . '/database/migrations/' . OfficialRouteCatalogSeedGenerator::MIGRATION . '.sql';
    foreach (array_slice($argv, 1) as $argument) {
        if (str_starts_with($argument, '--source=')) {
            $source = substr($argument, 9);
        } elseif (str_starts_with($argument, '--output=')) {
            $output = substr($argument, 9);
        }
    }
    $generator = new OfficialRouteCatalogSeedGenerator();
    $rows = $generator->read($source);
    if (file_put_contents($output, $generator->migration($rows), LOCK_EX) === false) {
        throw new RuntimeException('No fue posible escribir la migración generada.');
    }
    echo sprintf("Generadas %d rutas oficiales en %s\n", count($rows), $output);
}
