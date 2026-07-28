<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use RuntimeException;

final class ConsistVinExtractor
{
    public function __construct(private array $configuration)
    {
    }

    public function extract(array $file, array $definition): array
    {
        $path = (string) ($file['path'] ?? '');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException(sprintf('%s no está disponible para el análisis.', $definition['label']));
        }

        $readerType = (string) ($file['reader_type'] ?? IOFactory::identify($path));
        $worksheets = $this->reader($readerType, $path)->listWorksheetInfo($path);
        $header = $this->detectHeader($readerType, $path, $worksheets, $definition['required_headers']);
        if ($header === null || !isset($header['columns']['vin'])) {
            throw new RuntimeException(sprintf('No se localizó la columna VIN en %s.', $definition['label']));
        }

        $sheetInfo = $worksheets[$header['sheet_index']];
        $lastRow = (int) ($sheetInfo['totalRows'] ?? 0);
        $lastColumn = Coordinate::columnIndexFromString((string) ($sheetInfo['lastColumnLetter'] ?? 'A'));
        $vinColumn = (int) $header['columns']['vin'];
        $chunkSize = max(1, (int) $this->configuration['chunk_rows']);
        $vins = [];
        $duplicates = [];
        $emptyVins = 0;
        $emptyRows = 0;
        $validRecords = 0;

        for ($start = $header['row'] + 1; $start <= $lastRow; $start += $chunkSize) {
            $end = min($lastRow, $start + $chunkSize - 1);
            $spreadsheet = $this->loadRange($readerType, $path, $header['sheet'], $start, $end);
            $sheet = $spreadsheet->getSheetByName($header['sheet']) ?? $spreadsheet->getActiveSheet();
            for ($row = $start; $row <= $end; $row++) {
                $rowEmpty = true;
                for ($column = 1; $column <= $lastColumn; $column++) {
                    if (trim((string) $sheet->getCell([$column, $row])->getFormattedValue()) !== '') {
                        $rowEmpty = false;
                        break;
                    }
                }
                if ($rowEmpty) {
                    $emptyRows++;
                    continue;
                }

                $vin = $this->normalizeVin($sheet->getCell([$vinColumn, $row])->getFormattedValue());
                if ($vin === '') {
                    $emptyVins++;
                    continue;
                }
                $validRecords++;
                if (isset($vins[$vin])) {
                    $duplicates[$vin] = ($duplicates[$vin] ?? 0) + 1;
                    continue;
                }
                $vins[$vin] = true;
            }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return [
            'label' => (string) $definition['label'],
            'sheet' => $header['sheet'],
            'header_row' => $header['row'],
            'valid_records' => $validRecords,
            'unique_count' => count($vins),
            'duplicate_count' => array_sum($duplicates),
            'empty_vin' => $emptyVins,
            'empty_rows' => $emptyRows,
            'vins' => $vins,
            'duplicates' => $duplicates,
        ];
    }

    public function normalizeVin(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private function detectHeader(string $readerType, string $path, array $worksheets, array $required): ?array
    {
        $scanRows = max(1, (int) $this->configuration['header_scan_rows']);
        foreach ($worksheets as $sheetIndex => $info) {
            $sheetName = (string) ($info['worksheetName'] ?? '');
            $lastColumn = Coordinate::columnIndexFromString((string) ($info['lastColumnLetter'] ?? 'A'));
            $lastRow = min($scanRows, (int) ($info['totalRows'] ?? 0));
            $spreadsheet = $this->loadRange($readerType, $path, $sheetName, 1, $lastRow);
            $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();
            for ($row = 1; $row <= $lastRow; $row++) {
                $columns = [];
                foreach ($required as $canonical => $aliases) {
                    $normalizedAliases = array_map([$this, 'normalizeHeader'], $aliases);
                    for ($column = 1; $column <= $lastColumn; $column++) {
                        $candidate = $this->normalizeHeader($sheet->getCell([$column, $row])->getFormattedValue());
                        if ($candidate !== '' && in_array($candidate, $normalizedAliases, true)) {
                            $columns[$canonical] = $column;
                            break;
                        }
                    }
                }
                if (isset($columns['vin'])) {
                    $spreadsheet->disconnectWorksheets();
                    return ['sheet_index' => $sheetIndex, 'sheet' => $sheetName, 'row' => $row, 'columns' => $columns];
                }
            }
            $spreadsheet->disconnectWorksheets();
        }
        return null;
    }

    private function normalizeHeader(mixed $value): string
    {
        $header = str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], trim((string) $value));
        $header = mb_strtolower($header, 'UTF-8');
        $header = strtr($header, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','ç'=>'c']);
        $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $header) ?? '') ?? '');
    }

    private function loadRange(string $readerType, string $path, string $sheet, int $start, int $end): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = $this->reader($readerType, $path);
        $reader->setLoadSheetsOnly([$sheet]);
        $reader->setReadFilter(new ConsistVinReadFilter($start, $end));
        $reader->setReadDataOnly(true);
        return $reader->load($path);
    }

    private function reader(string $readerType, string $path): IReader
    {
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        if ($reader instanceof Csv) {
            $sample = (string) file_get_contents($path, false, null, 0, 8192);
            $sample = str_starts_with($sample, "\xEF\xBB\xBF") ? substr($sample, 3) : $sample;
            $reader->setInputEncoding(mb_detect_encoding($sample, ['UTF-8', 'Windows-1252'], true) ?: 'Windows-1252');
            $reader->setDelimiter($this->delimiter($sample));
        }
        return $reader;
    }

    private function delimiter(string $sample): string
    {
        $firstLine = strtok($sample, "\r\n") ?: '';
        $counts = [','=>substr_count($firstLine, ','), ';'=>substr_count($firstLine, ';'), "\t"=>substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);
        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }
}

final class ConsistVinReadFilter implements IReadFilter
{
    public function __construct(private int $startRow, private int $endRow)
    {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
