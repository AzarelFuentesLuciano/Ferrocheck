<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

final class ConsistSpreadsheetPreviewer
{
    private ConsistHeaderNormalizer $headerNormalizer;
    private ConsistFileTypeDetector $fileTypeDetector;

    public function __construct(
        private array $configuration,
        ?ConsistHeaderNormalizer $headerNormalizer = null,
        ?ConsistFileTypeDetector $fileTypeDetector = null,
    ) {
        $this->headerNormalizer = $headerNormalizer ?? new ConsistHeaderNormalizer();
        $this->fileTypeDetector = $fileTypeDetector
            ?? new ConsistFileTypeDetector($configuration, $this->headerNormalizer);
    }

    public function previewBatch(array $stagedFiles): array
    {
        $files = [];
        $valid = true;
        foreach ($this->configuration['files'] as $field => $definition) {
            if (!isset($stagedFiles[$field])) {
                throw new RuntimeException(sprintf('No existe el archivo temporal %s.', $definition['label']));
            }
            $files[$field] = $this->previewFile($field, $stagedFiles[$field], $definition);
            $valid = $valid && $files[$field]['valid'];
        }

        return ['valid' => $valid, 'files' => $files];
    }

    public function normalizeHeader(mixed $value): string
    {
        return $this->headerNormalizer->normalize($value);
    }

    public function normalizeVin(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private function previewFile(string $expectedType, array $file, array $definition): array
    {
        $path = (string) $file['path'];
        $readerType = (string) $file['reader_type'];
        $reader = $this->reader($readerType, $path);
        $worksheets = $reader->listWorksheetInfo($path);
        $required = $definition['required_headers'];
        $header = $this->detectHeader($readerType, $path, $worksheets);
        $typeDetection = $header !== null
            ? $this->fileTypeDetector->detect($expectedType, $header['headers'])
            : null;
        $expectedColumns = $header !== null
            ? $this->matchRequiredColumns($header['normalized'], $header['original'], $required)
            : ['columns' => [], 'found' => [], 'missing' => array_keys($required)];

        $result = [
            'label' => $definition['label'],
            'original_name' => $file['original_name'],
            'extension' => $file['extension'],
            'detected_type' => $readerType,
            'detected_file_type' => $typeDetection?->detectedType,
            'expected_file_type' => $expectedType,
            'mime' => $file['mime'],
            'size' => $file['size'],
            'sheet' => $header['sheet'] ?? null,
            'header_row' => $header['row'] ?? null,
            'found_columns' => $expectedColumns['found'],
            'missing_required' => $expectedColumns['missing'],
            'identity_matches' => $typeDetection?->matches ?? [],
            'matched_identity_headers' => $typeDetection?->matchedIdentityHeaders ?? 0,
            'total_identity_headers' => $typeDetection?->totalIdentityHeaders ?? count($definition['identity_headers'] ?? []),
            'missing_identity_headers' => $typeDetection?->missingIdentityHeaders ?? ($definition['identity_headers'] ?? []),
            'identity_ambiguous' => $typeDetection?->isAmbiguous ?? false,
            'identity_unknown' => $typeDetection?->isUnknown ?? true,
            'empty_rows' => 0,
            'empty_vin' => 0,
            'duplicate_vin' => 0,
            'valid_records' => 0,
            'errors' => [],
            'sample' => [],
            'valid' => false,
        ];
        if ($header === null || $result['missing_required'] !== []) {
            $result['errors'][] = 'No se encontró una columna VIN reconocible.';
            return $result;
        }
        if ($typeDetection === null || !$typeDetection->isValid) {
            $result['errors'][] = $this->identityErrorMessage($definition, $typeDetection);
            return $result;
        }

        $sheetInfo = $worksheets[$header['sheet_index']];
        $lastRow = (int) ($sheetInfo['totalRows'] ?? 0);
        $lastColumnIndex = Coordinate::columnIndexFromString((string) ($sheetInfo['lastColumnLetter'] ?? 'A'));
        $vinColumn = (int) $expectedColumns['columns']['vin'];
        $seen = [];
        $chunkSize = max(1, (int) $this->configuration['chunk_rows']);

        for ($start = $header['row'] + 1; $start <= $lastRow; $start += $chunkSize) {
            $end = min($lastRow, $start + $chunkSize - 1);
            $spreadsheet = $this->loadRange($readerType, $path, $header['sheet'], $start, $end);
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
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        $result['valid'] = $result['missing_required'] === [] && $typeDetection->isValid;
        return $result;
    }

    private function detectHeader(string $readerType, string $path, array $worksheets): ?array
    {
        $configuredAliases = $this->configuration['vin_aliases'] ?? ['vin'];
        foreach ($this->configuration['files'] ?? [] as $definition) {
            $configuredAliases = [
                ...$configuredAliases,
                ...($definition['required_headers']['vin'] ?? []),
            ];
        }
        $vinAliases = array_values(array_unique(array_map(
            fn (mixed $alias): string => $this->normalizeHeader($alias),
            $configuredAliases,
        )));
        $scanRows = max(1, (int) $this->configuration['header_scan_rows']);
        foreach ($worksheets as $sheetIndex => $info) {
            $sheetName = (string) ($info['worksheetName'] ?? '');
            $lastColumnIndex = Coordinate::columnIndexFromString((string) ($info['lastColumnLetter'] ?? 'A'));
            $lastRow = min($scanRows, (int) ($info['totalRows'] ?? 0));
            $spreadsheet = $this->loadRange($readerType, $path, $sheetName, 1, $lastRow);
            $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();
            for ($row = 1; $row <= $lastRow; $row++) {
                $normalized = [];
                $original = [];
                for ($column = 1; $column <= $lastColumnIndex; $column++) {
                    $raw = trim((string) $sheet->getCell([$column, $row])->getFormattedValue());
                    $original[$column] = $raw;
                    $normalized[$column] = $this->normalizeHeader($raw);
                }
                if (array_intersect($vinAliases, $normalized) !== []) {
                    $spreadsheet->disconnectWorksheets();
                    return [
                        'sheet_index' => $sheetIndex,
                        'sheet' => $sheetName,
                        'row' => $row,
                        'headers' => array_values($original),
                        'original' => $original,
                        'normalized' => $normalized,
                    ];
                }
            }
            $spreadsheet->disconnectWorksheets();
        }

        return null;
    }

    private function matchRequiredColumns(array $normalized, array $original, array $required): array
    {
        $columns = [];
        $found = [];
        foreach ($required as $canonical => $aliases) {
            $normalizedAliases = array_map(fn (mixed $alias): string => $this->normalizeHeader($alias), $aliases);
            foreach ($normalized as $column => $candidate) {
                if ($candidate !== '' && in_array($candidate, $normalizedAliases, true)) {
                    $columns[$canonical] = $column;
                    $found[$canonical] = $original[$column];
                    break;
                }
            }
        }

        return [
            'columns' => $columns,
            'found' => $found,
            'missing' => array_values(array_diff(array_keys($required), array_keys($columns))),
        ];
    }

    private function identityErrorMessage(array $expectedDefinition, ?ConsistFileTypeDetection $detection): string
    {
        $expectedLabel = (string) ($expectedDefinition['label'] ?? 'archivo');
        if ($detection?->isAmbiguous) {
            return 'No fue posible identificar de forma segura el tipo del archivo cargado.';
        }
        if ($detection === null || $detection->isUnknown) {
            return sprintf(
                'El archivo cargado en “%s” no coincide con ninguna estructura reconocida. Verifique que sea el archivo original generado por el sistema correspondiente.',
                $expectedLabel,
            );
        }

        $detectedLabel = (string) (
            $this->configuration['files'][$detection->detectedType]['label']
            ?? $detection->detectedType
            ?? 'otro tipo'
        );

        return sprintf(
            'El archivo cargado en “%s” no corresponde al formato esperado. El sistema detectó que parece ser un archivo “%s”. Seleccione el archivo correcto y vuelva a intentarlo.',
            $expectedLabel,
            $detectedLabel,
        );
    }

    private function loadRange(string $readerType, string $path, string $sheet, int $start, int $end): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = $this->reader($readerType, $path);
        $reader->setLoadSheetsOnly([$sheet]);
        $reader->setReadFilter(new ConsistChunkReadFilter($start, $end));
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
    public function __construct(private int $startRow, private int $endRow)
    {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
