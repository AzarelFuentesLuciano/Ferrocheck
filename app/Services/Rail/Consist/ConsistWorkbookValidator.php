<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use RuntimeException;
use ZipArchive;

final class ConsistWorkbookValidator
{
    public function validate(string $path): array
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('La exportación debe usar extensión .xlsx.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('El archivo exportado no es un paquete XLSX válido.');
        }
        try {
            $names = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $names[] = (string) $zip->getNameIndex($index);
            }
            $forbidden = array_filter($names, static fn (string $name): bool =>
                str_starts_with($name, 'xl/externalLinks/')
                || str_starts_with($name, 'xl/connections')
                || str_contains(strtolower($name), 'vba')
                || str_starts_with($name, 'xl/queryTables/')
            );
            $workbook = (string) $zip->getFromName('xl/workbook.xml');
            preg_match_all('/<sheet[^>]+name="([^"]+)"/u', $workbook, $matches);
            $sheets = array_map('html_entity_decode', $matches[1] ?? []);
            $formulas = 0;
            foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml', 'xl/worksheets/sheet3.xml'] as $sheet) {
                $formulas += preg_match_all('/<f(?:\s|>)/u', (string) $zip->getFromName($sheet));
            }
            if ($forbidden !== [] || $formulas !== 0 || $sheets !== ['Consist', 'Summary', 'Version']) {
                throw new RuntimeException('El XLSX exportado contiene dependencias o estructura no permitidas.');
            }
            return [
                'extension' => 'xlsx', 'formulas' => 0, 'external_links' => 0,
                'connections' => 0, 'query_tables' => 0, 'macros' => 0, 'sheets' => $sheets,
            ];
        } finally {
            $zip->close();
        }
    }
}
