<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ConsistSpreadsheetPreviewer
{
    private ConsistHeaderNormalizer $headerNormalizer;

    public function __construct(
        private array $configuration,
        ?ConsistHeaderNormalizer $headerNormalizer = null,
    ) {
        $this->headerNormalizer = $headerNormalizer ?? new ConsistHeaderNormalizer();
    }

    public function previewBatch(array $stagedFiles, ?array $validatedIdentities = null): array
    {
        $validatedIdentities ??= (new ConsistSpreadsheetIdentityValidator(
            $this->configuration,
            $this->headerNormalizer,
        ))->validateBatch($stagedFiles);
        $files = [];
        foreach ($this->configuration['files'] as $field => $definition) {
            if (!isset($stagedFiles[$field], $validatedIdentities[$field])) {
                throw new ConsistUploadValidationException(
                    sprintf('No fue posible validar el archivo temporal %s.', $definition['label']),
                );
            }
            $files[$field] = $this->previewFile(
                $field,
                $stagedFiles[$field],
                $definition,
                $validatedIdentities[$field],
            );
        }

        return ['valid' => true, 'files' => $files];
    }

    public function normalizeHeader(mixed $value): string
    {
        return $this->headerNormalizer->normalize($value);
    }

    public function normalizeVin(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private function previewFile(string $expectedType, array $file, array $definition, array $header): array
    {
        $path = (string) $file['path'];
        $readerType = (string) $file['reader_type'];
        $reader = $this->reader($readerType, $path);
        $worksheets = $reader->listWorksheetInfo($path);
        unset($reader);
        $detection = $header['detection'] ?? [];

        $result = [
            'label' => $definition['label'],
            'original_name' => $file['original_name'],
            'extension' => $file['extension'],
            'detected_type' => $readerType,
            'detected_file_type' => $detection['detected_type'] ?? null,
            'expected_file_type' => $expectedType,
            'mime' => $file['mime'],
            'size' => $file['size'],
            'sheet' => $header['sheet'] ?? null,
            'header_row' => $header['row'] ?? null,
            'validated_header' => [
                'sheet_index' => $header['sheet_index'],
                'sheet' => $header['sheet'],
                'row' => $header['row'],
                'columns' => $header['columns'],
            ],
            'found_columns' => $header['found'] ?? [],
            'missing_required' => [],
            'identity_matches' => $detection['matches'] ?? [],
            'matched_identity_headers' => $detection['matched_identity_headers'] ?? 0,
            'total_identity_headers' => $detection['total_identity_headers'] ?? count($definition['identity_headers'] ?? []),
            'missing_identity_headers' => $detection['missing_identity_headers'] ?? [],
            'identity_ambiguous' => false,
            'identity_unknown' => false,
            'empty_rows' => 0,
            'empty_vin' => 0,
            'duplicate_vin' => 0,
            'valid_records' => 0,
            'errors' => [],
            'sample' => [],
            'valid' => false,
        ];
        $sheetInfo = $worksheets[$header['sheet_index']] ?? null;
        if (!is_array($sheetInfo)) {
            throw new ConsistUploadValidationException(
                sprintf('La primera hoja de “%s” no tiene una estructura válida.', $definition['label']),
            );
        }
        $lastRow = (int) ($sheetInfo['totalRows'] ?? 0);
        if ($lastRow < 1 || $lastRow > (int) ($this->configuration['max_worksheet_rows'] ?? 250000)) {
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” reporta dimensiones no válidas.', $definition['label']),
            );
        }
        try {
            $reportedLastColumn = Coordinate::columnIndexFromString((string) ($sheetInfo['lastColumnLetter'] ?? 'A'));
        } catch (\Throwable) {
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” reporta columnas no válidas.', $definition['label']),
            );
        }
        $maxColumns = max(1, (int) ($this->configuration['header_scan_max_columns'] ?? 100));
        if ($reportedLastColumn < 1 || $reportedLastColumn > $maxColumns) {
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” reporta dimensiones de columnas no válidas.', $definition['label']),
            );
        }
        $lastColumnIndex = $reportedLastColumn;
        $vinColumn = (int) $header['columns']['vin'];
        if ($vinColumn < 1 || $vinColumn > $lastColumnIndex) {
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” reporta una columna VIN no válida.', $definition['label']),
            );
        }
        $seen = [];
        $chunkSize = max(1, (int) $this->configuration['chunk_rows']);

        for ($start = $header['row'] + 1; $start <= $lastRow; $start += $chunkSize) {
            $end = min($lastRow, $start + $chunkSize - 1);
            $spreadsheet = null;
            try {
                $spreadsheet = $this->loadRange($readerType, $path, $header['sheet'], $start, $end, $lastColumnIndex);
                $sheet = $spreadsheet->getSheetByName($header['sheet']) ?? $spreadsheet->getActiveSheet();
                for ($row = $start; $row <= $end; $row++) {
                    $values = [];
                    $isEmpty = true;
                    for ($column = 1; $column <= $lastColumnIndex; $column++) {
                        $value = trim((string) $sheet->getCell([$column, $row])->getFormattedValue());
                        $values[$column] = $value;
                        $isEmpty = $isEmpty && $value === '';
                    }
                    if ($isEmpty) {
                        $result['empty_rows']++;
                        continue;
                    }

                    $vin = $this->normalizeVin($values[$vinColumn] ?? '');
                    if ($vin === '') {
                        $result['empty_vin']++;
                        $this->addError($result['errors'], sprintf('Fila %d: VIN vacío.', $row));
                        continue;
                    }
                    if (isset($seen[$vin])) {
                        $result['duplicate_vin']++;
                        $this->addError($result['errors'], sprintf('Fila %d: VIN duplicado %s.', $row, $vin));
                        continue;
                    }
                    $seen[$vin] = true;
                    $result['valid_records']++;
                    if (count($result['sample']) < (int) $this->configuration['sample_limit']) {
                        $result['sample'][] = ['row' => $row, 'vin' => $vin];
                    }
                }
            } finally {
                if ($spreadsheet !== null) {
                    $spreadsheet->disconnectWorksheets();
                }
                unset($spreadsheet);
            }
        }

        $result['valid'] = true;
        return $result;
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
        $reader->setReadFilter(new ConsistChunkReadFilter($start, $end, $maxColumn));
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
            $encoding = mb_detect_encoding($sample, ['UTF-8', 'Windows-1252'], true) ?: 'Windows-1252';
            $reader->setInputEncoding($encoding);
            $reader->setDelimiter($this->detectDelimiter($sample));
        }
        return $reader;
    }

    private function detectDelimiter(string $sample): string
    {
        $firstLine = strtok($sample, "\r\n") ?: '';
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);
        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function addError(array &$errors, string $message): void
    {
        if (count($errors) < (int) $this->configuration['error_limit']) {
            $errors[] = $message;
        }
    }
}

final class ConsistChunkReadFilter implements IReadFilter
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
