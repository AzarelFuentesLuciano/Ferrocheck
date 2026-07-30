<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class ConsistUploadValidator
{
    /** @var callable(string): bool */
    private $uploadedFileCheck;

    public function __construct(private array $configuration, ?callable $uploadedFileCheck = null)
    {
        $this->uploadedFileCheck = $uploadedFileCheck ?? 'is_uploaded_file';
    }

    public function validateBatch(array $files): array
    {
        $validated = [];
        $totalSize = 0;

        foreach ($this->configuration['files'] as $field => $definition) {
            $file = $files[$field] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new ConsistUploadValidationException(sprintf('El archivo %s es obligatorio.', $definition['label']));
            }
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new ConsistUploadValidationException(sprintf('No fue posible recibir %s.', $definition['label']));
            }

            $temporaryPath = (string) ($file['tmp_name'] ?? '');
            if ($temporaryPath === '' || !is_file($temporaryPath) || !is_readable($temporaryPath)) {
                throw new ConsistUploadValidationException(sprintf('El archivo temporal de %s no es válido.', $definition['label']));
            }
            if (!(($this->uploadedFileCheck)($temporaryPath))) {
                throw new ConsistUploadValidationException(sprintf('La carga de %s no fue recibida por HTTP.', $definition['label']));
            }

            $size = (int) ($file['size'] ?? filesize($temporaryPath));
            if ($size <= 0 || $size > (int) $this->configuration['max_file_size']) {
                throw new ConsistUploadValidationException(sprintf('%s excede el límite permitido o está vacío.', $definition['label']));
            }
            $totalSize += $size;

            $originalName = basename((string) ($file['name'] ?? ''));
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->configuration['allowed_extensions'], true)) {
                throw new ConsistUploadValidationException(sprintf('%s debe ser XLSX, XLS o CSV.', $definition['label']));
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) ?: 'application/octet-stream';
            $allowedMimes = [
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
                'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2'],
                'csv' => ['text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel'],
            ];
            if (!in_array($mime, $allowedMimes[$extension], true)) {
                throw new ConsistUploadValidationException(sprintf('El tipo MIME de %s no está permitido.', $definition['label']));
            }
            $readerType = IOFactory::identify($temporaryPath);
            $expectedReader = ['xlsx' => 'Xlsx', 'xls' => 'Xls', 'csv' => 'Csv'][$extension];
            if ($readerType !== $expectedReader) {
                throw new ConsistUploadValidationException(sprintf('El contenido de %s no coincide con su extensión.', $definition['label']));
            }

            $validated[$field] = [
                'field' => $field,
                'label' => $definition['label'],
                'tmp_path' => $temporaryPath,
                'original_name' => $originalName,
                'extension' => $extension,
                'mime' => $mime,
                'reader_type' => $readerType,
                'size' => $size,
            ];
        }

        if ($totalSize > (int) $this->configuration['max_batch_size']) {
            throw new ConsistUploadValidationException('El lote supera el límite total permitido.');
        }

        return $validated;
    }
}
