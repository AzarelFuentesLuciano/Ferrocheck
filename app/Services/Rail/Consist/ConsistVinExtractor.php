<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use RuntimeException;

class ConsistVinExtractor
{
    private ConsistHeaderNormalizer $headerNormalizer;

    public function __construct(
        private array $configuration,
        ?ConsistHeaderNormalizer $headerNormalizer = null,
    ) {
        $this->headerNormalizer = $headerNormalizer ?? new ConsistHeaderNormalizer();
    }

    public function extract(array $file, array $definition, ?array $validatedHeader = null): array
    {
        $path = (string) ($file['path'] ?? '');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException(sprintf('%s no está disponible para el análisis.', $definition['label']));
        }

        $readerType = (string) ($file['reader_type'] ?? IOFactory::identify($path));
        $worksheets = $this->reader($readerType, $path)->listWorksheetInfo($path);
        $header = $validatedHeader
            ?? $this->detectHeader($readerType, $path, $worksheets, $definition['required_headers']);
        if ($header === null || !isset($header['columns']['vin'])) {
            throw new RuntimeException(sprintf('No se localizó la columna VIN en %s.', $definition['label']));
        }

        $sheetInfo = $worksheets[$header['sheet_index']];
        $lastRow = (int) ($sheetInfo['totalRows'] ?? 0);
        if ($lastRow < 1 || $lastRow > (int) ($this->configuration['max_worksheet_rows'] ?? 250000)) {
            throw new RuntimeException(sprintf('%s reporta dimensiones no válidas.', $definition['label']));
        }
        try {
            $lastColumn = Coordinate::columnIndexFromString((string) ($sheetInfo['lastColumnLetter'] ?? 'A'));
        } catch (\Throwable) {
            throw new RuntimeException(sprintf('%s reporta columnas no válidas.', $definition['label']));
        }
        $maxColumns = max(1, (int) ($this->configuration['header_scan_max_columns'] ?? 100));
        if ($lastColumn < 1 || $lastColumn > $maxColumns) {
            throw new RuntimeException(sprintf('%s reporta dimensiones de columnas no válidas.', $definition['label']));
        }
        $vinColumn = (int) $header['columns']['vin'];
        $chunkSize = max(1, (int) $this->configuration['chunk_rows']);
        $vins = [];
        $duplicates = [];
        $emptyVins = 0;
        $emptyRows = 0;
        $validRecords = 0;

        for ($start = $header['row'] + 1; $start <= $lastRow; $start += $chunkSize) {
            $end = min($lastRow, $start + $chunkSize - 1);
            $spreadsheet = null;
            try {
                $spreadsheet = $this->loadRange($readerType, $path, $header['sheet'], $start, $end, $lastColumn);
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
            } finally {
                if ($spreadsheet !== null) {
                    $spreadsheet->disconnectWorksheets();
                }
                unset($spreadsheet);
            }
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
            $lastColumn = min(
                Coordinate::columnIndexFromString((string) ($info['lastColumnLetter'] ?? 'A')),
                max(1, (int) ($this->configuration['header_scan_max_columns'] ?? 100)),
            );
            $lastRow = min($scanRows, (int) ($info['totalRows'] ?? 0));
            $spreadsheet = null;
            try {
                $spreadsheet = $this->loadRange($readerType, $path, $sheetName, 1, $lastRow, $lastColumn);
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
                        return ['sheet_index' => $sheetIndex, 'sheet' => $sheetName, 'row' => $row, 'columns' => $columns];
                    }
                }
            } finally {
                if ($spreadsheet !== null) {
                    $spreadsheet->disconnectWorksheets();
                }
                unset($spreadsheet);
            }
        }
        return null;
    }

    private function normalizeHeader(mixed $value): string
    {
        return $this->headerNormalizer->normalize($value);
    }

    private function loadRange(
        string $readerType,
        string $path,
        string $sheet,
        int $start,
        int $end,
        int $maxColumn,
    ): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = $this->reader($readerType, $path);
        $reader->setLoadSheetsOnly([$sheet]);
        $reader->setReadFilter(new ConsistVinReadFilter($start, $end, $maxColumn));
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
    public function __construct(
        private int $startRow,
        private int $endRow,
        private int $maxColumn,
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row < $this->startRow || $row > $this->endRow) {
            return false;
        }

        try {
            return Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
        } catch (\Throwable) {
            return false;
        }
    }
}
