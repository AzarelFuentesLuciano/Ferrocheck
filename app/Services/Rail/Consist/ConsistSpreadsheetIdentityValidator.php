<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ConsistSpreadsheetIdentityValidator
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

    public function validateBatch(array $stagedFiles): array
    {
        $validated = [];
        foreach ($this->configuration['files'] as $field => $definition) {
            if (!isset($stagedFiles[$field])) {
                throw new ConsistUploadValidationException(
                    sprintf('El archivo %s es obligatorio.', $definition['label']),
                );
            }
            $validated[$field] = $this->validateFile($field, $stagedFiles[$field], $definition);
        }

        return $validated;
    }

    public function validateFile(string $expectedType, array $file, array $definition): array
    {
        $path = (string) ($file['path'] ?? '');
        $label = (string) ($definition['label'] ?? $expectedType);
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” no está disponible para validación.', $label),
            );
        }

        $readerType = (string) ($file['reader_type'] ?? '');
        try {
            $header = $readerType === 'Csv'
                ? $this->scanCsvHeader($path)
                : $this->scanSpreadsheetHeader($readerType, $path);
        } catch (ConsistUploadValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ConsistUploadValidationException(
                sprintf('No fue posible validar la estructura del archivo cargado en “%s”.', $label),
            );
        }

        if ($header === null) {
            throw new ConsistUploadValidationException(
                sprintf(
                    'El archivo cargado en “%s” no contiene un encabezado VIN reconocido dentro de las primeras %d filas.',
                    $label,
                    $this->scanRows(),
                ),
            );
        }

        $required = $this->matchRequiredColumns(
            $header['normalized'],
            $header['original'],
            $definition['required_headers'] ?? [],
        );
        $detection = $this->fileTypeDetector->detect($expectedType, $header['headers']);
        if ($required['missing'] !== []) {
            if (!$detection->isUnknown && !$detection->isAmbiguous && $detection->detectedType !== $expectedType) {
                throw new ConsistUploadValidationException($this->identityErrorMessage($definition, $detection));
            }
            throw new ConsistUploadValidationException(
                sprintf('El archivo cargado en “%s” no contiene el encabezado VIN esperado.', $label),
            );
        }

        if (!$detection->isValid) {
            throw new ConsistUploadValidationException($this->identityErrorMessage($definition, $detection));
        }

        return $header + [
            'columns' => $required['columns'],
            'found' => $required['found'],
            'missing' => [],
            'detection' => $detection->toArray(),
        ];
    }

    private function scanCsvHeader(string $path): ?array
    {
        $sample = (string) file_get_contents($path, false, null, 0, 8192);
        $sampleWithoutBom = str_starts_with($sample, "\xEF\xBB\xBF") ? substr($sample, 3) : $sample;
        $encoding = mb_detect_encoding($sampleWithoutBom, ['UTF-8', 'Windows-1252'], true) ?: 'Windows-1252';
        $delimiter = $this->detectDelimiter($sampleWithoutBom);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ConsistUploadValidationException('No fue posible abrir el archivo CSV para validación.');
        }

        try {
            for ($row = 1; $row <= $this->scanRows(); $row++) {
                $values = fgetcsv($handle, 0, $delimiter);
                if ($values === false) {
                    break;
                }
                $values = array_slice($values, 0, $this->scanColumns());
                if ($encoding !== 'UTF-8') {
                    $values = array_map(
                        static fn (mixed $value): string => mb_convert_encoding((string) $value, 'UTF-8', $encoding),
                        $values,
                    );
                }
                $header = $this->headerCandidate($values, $row, 'Worksheet', 0);
                if ($header !== null) {
                    return $header;
                }
            }
        } finally {
            fclose($handle);
        }

        return null;
    }

    private function scanSpreadsheetHeader(string $readerType, string $path): ?array
    {
        if (!in_array($readerType, ['Xlsx', 'Xls'], true)) {
            throw new ConsistUploadValidationException('El formato del archivo no es válido para Consist Rail.');
        }

        $reader = $this->reader($readerType);
        $sheetNames = $reader->listWorksheetNames($path);
        unset($reader);
        $sheetName = isset($sheetNames[0]) ? (string) $sheetNames[0] : '';
        if ($sheetName === '') {
            throw new ConsistUploadValidationException('El archivo no contiene una primera hoja legible.');
        }

        $reader = $this->reader($readerType);
        $reader->setLoadSheetsOnly([$sheetName]);
        $reader->setReadFilter(new ConsistHeaderReadFilter($this->scanRows(), $this->scanColumns()));
        $spreadsheet = null;
        try {
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();
            for ($row = 1; $row <= $this->scanRows(); $row++) {
                $values = [];
                for ($column = 1; $column <= $this->scanColumns(); $column++) {
                    $values[] = $sheet->getCell([$column, $row])->getValue();
                }
                $header = $this->headerCandidate($values, $row, $sheetName, 0);
                if ($header !== null) {
                    return $header;
                }
            }
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }
            unset($spreadsheet, $reader);
        }

        return null;
    }

    private function headerCandidate(array $values, int $row, string $sheet, int $sheetIndex): ?array
    {
        $original = [];
        $normalized = [];
        foreach ($values as $index => $value) {
            $column = $index + 1;
            $original[$column] = trim((string) $value);
            $normalized[$column] = $this->headerNormalizer->normalize($value);
        }
        if (array_intersect($this->vinAliases(), $normalized) === []) {
            return null;
        }

        return [
            'sheet_index' => $sheetIndex,
            'sheet' => $sheet,
            'row' => $row,
            'headers' => array_values($original),
            'original' => $original,
            'normalized' => $normalized,
        ];
    }

    private function matchRequiredColumns(array $normalized, array $original, array $required): array
    {
        $columns = [];
        $found = [];
        foreach ($required as $canonical => $aliases) {
            $normalizedAliases = array_map(
                fn (mixed $alias): string => $this->headerNormalizer->normalize($alias),
                $aliases,
            );
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

    private function identityErrorMessage(array $expectedDefinition, ConsistFileTypeDetection $detection): string
    {
        $expectedLabel = (string) ($expectedDefinition['label'] ?? 'archivo');
        if ($detection->isAmbiguous) {
            return 'No fue posible identificar de forma segura el tipo del archivo cargado.';
        }
        if ($detection->isUnknown) {
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

    private function vinAliases(): array
    {
        $aliases = $this->configuration['vin_aliases'] ?? ['vin'];
        foreach ($this->configuration['files'] ?? [] as $definition) {
            $aliases = [...$aliases, ...($definition['required_headers']['vin'] ?? [])];
        }

        return array_values(array_unique(array_map(
            fn (mixed $alias): string => $this->headerNormalizer->normalize($alias),
            $aliases,
        )));
    }

    private function reader(string $readerType): IReader
    {
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setIncludeCharts')) {
            $reader->setIncludeCharts(false);
        }

        return $reader;
    }

    private function scanRows(): int
    {
        return max(1, (int) ($this->configuration['header_scan_rows'] ?? 50));
    }

    private function scanColumns(): int
    {
        return max(1, (int) ($this->configuration['header_scan_max_columns'] ?? 100));
    }

    private function detectDelimiter(string $sample): string
    {
        $firstLine = strtok($sample, "\r\n") ?: '';
        $counts = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        return ($counts[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }
}

final class ConsistHeaderReadFilter implements IReadFilter
{
    public function __construct(
        private int $maxRow,
        private int $maxColumn,
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row < 1 || $row > $this->maxRow) {
            return false;
        }

        try {
            return Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
        } catch (Throwable) {
            return false;
        }
    }
}
