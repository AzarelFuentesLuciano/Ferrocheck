<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Import;

final class ScannerImportStagingStore
{
    private const SESSION_KEY = 'control_escaneres_import_staging';

    public function __construct(private string $root, private array &$session) {}

    public function stage(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new \InvalidArgumentException('Selecciona un archivo Excel válido.');
        }
        $name = (string) ($file['name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (!preg_match('/\.xlsx$/i', $name) || $size < 1 || $size > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('El archivo debe ser XLSX y no exceder 10 MB.');
        }
        if (!is_dir($this->root) && !mkdir($this->root, 0750, true) && !is_dir($this->root)) {
            throw new \RuntimeException('No fue posible preparar la vista previa.');
        }
        $token = bin2hex(random_bytes(32));
        $path = $this->root . DIRECTORY_SEPARATOR . $token . '.xlsx';
        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            throw new \RuntimeException('No fue posible conservar temporalmente el archivo.');
        }
        $this->discardExpired();
        $this->session[self::SESSION_KEY][$token] = [
            'path' => $path,
            'sha256' => hash_file('sha256', $path),
            'expires' => time() + 1800,
        ];
        return ['token' => $token, 'path' => $path];
    }

    public function resolve(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new \DomainException('La confirmación de importación no es válida.');
        }
        $meta = $this->session[self::SESSION_KEY][$token] ?? null;
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            throw new \DomainException('La vista previa expiró; vuelve a cargar el archivo.');
        }
        $path = (string) ($meta['path'] ?? '');
        $root = realpath($this->root);
        $real = realpath($path);
        if ($root === false || $real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR) || !is_file($real)) {
            throw new \DomainException('El archivo temporal ya no está disponible.');
        }
        if (!hash_equals((string) $meta['sha256'], (string) hash_file('sha256', $real))) {
            throw new \DomainException('El archivo temporal cambió después de la vista previa.');
        }
        return $real;
    }

    public function discard(string $token): void
    {
        $meta = $this->session[self::SESSION_KEY][$token] ?? null;
        if (is_array($meta) && is_file((string) ($meta['path'] ?? ''))) {
            @unlink((string) $meta['path']);
        }
        unset($this->session[self::SESSION_KEY][$token]);
    }

    private function discardExpired(): void
    {
        foreach (($this->session[self::SESSION_KEY] ?? []) as $token => $meta) {
            if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
                $this->discard((string) $token);
            }
        }
    }
}
