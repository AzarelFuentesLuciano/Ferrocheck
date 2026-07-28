<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use RuntimeException;
use Throwable;

final class ConsistTemporaryUploadStore
{
    private const SESSION_KEY = '_consist_rail_uploads';
    /** @var callable(string, string): bool */
    private $fileMover;

    public function __construct(
        private string $root,
        private array &$session,
        private int $userId,
        private string $sessionId,
        private int $expiresSeconds = 1800,
        ?callable $fileMover = null,
    ) {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->fileMover = $fileMover ?? 'move_uploaded_file';
    }

    public function stageBatch(array $validated): array
    {
        $this->ensureRoot();
        $this->cleanupExpired();
        $token = bin2hex(random_bytes(32));
        $batchDirectory = $this->root . DIRECTORY_SEPARATOR . $token;
        if (!mkdir($batchDirectory, 0750) && !is_dir($batchDirectory)) {
            throw new RuntimeException('No fue posible crear el almacenamiento temporal.');
        }

        $stored = [];
        try {
            foreach ($validated as $field => $file) {
                $storedName = bin2hex(random_bytes(32)) . '.' . $file['extension'];
                $target = $batchDirectory . DIRECTORY_SEPARATOR . $storedName;
                if (!(($this->fileMover)($file['tmp_path'], $target))) {
                    throw new RuntimeException('No fue posible resguardar el lote completo.');
                }
                @chmod($target, 0640);
                $stored[$field] = array_diff_key($file, ['tmp_path' => true]) + [
                    'stored_name' => $storedName,
                    'path' => $target,
                    'sha256' => hash_file('sha256', $target),
                ];
            }
        } catch (Throwable $exception) {
            $this->removeDirectory($batchDirectory);
            throw $exception;
        }

        $entry = [
            'token' => $token,
            'user_id' => $this->userId,
            'session_hash' => hash('sha256', $this->sessionId),
            'created_at' => time(),
            'expires_at' => time() + $this->expiresSeconds,
            'files' => $stored,
            'preview' => null,
        ];
        $this->session[self::SESSION_KEY][$token] = $entry;

        return $entry;
    }

    public function resolve(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new RuntimeException('El lote temporal solicitado no es válido.');
        }
        $entry = $this->session[self::SESSION_KEY][$token] ?? null;
        if (!is_array($entry)) {
            throw new RuntimeException('El lote temporal solicitado no existe.');
        }
        if (($entry['user_id'] ?? null) !== $this->userId) {
            throw new RuntimeException('El lote temporal pertenece a otro usuario.');
        }
        if (!hash_equals((string) ($entry['session_hash'] ?? ''), hash('sha256', $this->sessionId))) {
            throw new RuntimeException('El lote temporal pertenece a otra sesión.');
        }
        if ((int) ($entry['expires_at'] ?? 0) < time()) {
            $this->discard($token);
            throw new RuntimeException('El lote temporal solicitado expiró.');
        }

        foreach ($entry['files'] ?? [] as $file) {
            $path = realpath((string) ($file['path'] ?? ''));
            $root = realpath($this->root);
            if ($path === false || !is_file($path)) {
                throw new RuntimeException('Falta un archivo temporal requerido por el lote.');
            }
            if ($root === false || !$this->isWithin($path, $root)) {
                throw new RuntimeException('La ruta de un archivo temporal no es válida.');
            }
            if (!hash_equals((string) $file['sha256'], (string) hash_file('sha256', $path))) {
                throw new RuntimeException('Un archivo temporal fue alterado después de su carga.');
            }
        }

        return $entry;
    }

    public function savePreview(string $token, array $preview): void
    {
        $this->resolve($token);
        $this->session[self::SESSION_KEY][$token]['preview'] = $preview;
    }

    public function preview(string $token): ?array
    {
        $entry = $this->resolve($token);
        return is_array($entry['preview'] ?? null) ? $entry['preview'] : null;
    }

    public function discard(string $token): void
    {
        $entry = $this->session[self::SESSION_KEY][$token] ?? null;
        if (is_array($entry)) {
            $directory = $this->root . DIRECTORY_SEPARATOR . $token;
            $this->removeDirectory($directory);
        }
        unset($this->session[self::SESSION_KEY][$token]);
    }

    public function cleanupExpired(): void
    {
        foreach ($this->session[self::SESSION_KEY] ?? [] as $token => $entry) {
            if (!is_array($entry) || (int) ($entry['expires_at'] ?? 0) < time()) {
                $this->discard((string) $token);
            }
        }
    }

    private function ensureRoot(): void
    {
        if (!is_dir($this->root) && !mkdir($this->root, 0750, true) && !is_dir($this->root)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento temporal.');
        }
    }

    private function removeDirectory(string $directory): void
    {
        $root = realpath($this->root);
        $resolved = realpath($directory);
        if ($root === false || $resolved === false || !$this->isWithin($resolved, $root)) {
            return;
        }
        foreach (scandir($resolved) ?: [] as $name) {
            if ($name !== '.' && $name !== '..') {
                $path = $resolved . DIRECTORY_SEPARATOR . $name;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
        rmdir($resolved);
    }

    private function isWithin(string $path, string $root): bool
    {
        return $path !== $root && str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }
}
