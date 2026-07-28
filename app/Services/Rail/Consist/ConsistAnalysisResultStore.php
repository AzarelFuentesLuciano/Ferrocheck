<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use JsonException;
use RuntimeException;

final class ConsistAnalysisResultStore
{
    private const FILE_NAME = 'vin-analysis.json';

    public function __construct(
        private ConsistTemporaryUploadStore $temporaryStore,
        private int $userId,
        private string $sessionId,
    ) {
    }

    public function save(string $token, array $result): void
    {
        $entry = $this->temporaryStore->resolve($token);
        $path = $this->resultPath($entry);
        $payload = [
            'token' => $token,
            'user_id' => $this->userId,
            'session_hash' => hash('sha256', $this->sessionId),
            'expires_at' => (int) $entry['expires_at'],
            'result' => $result,
        ];
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $envelope = json_encode([
            'payload' => $payload,
            'signature' => hash_hmac('sha256', $payloadJson, $this->signingKey()),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (file_put_contents($temporary, $envelope, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('No fue posible guardar el resumen temporal del cruce.');
        }
        @chmod($path, 0640);
    }

    public function load(string $token): ?array
    {
        $entry = $this->temporaryStore->resolve($token);
        $path = $this->resultPath($entry);
        if (!is_file($path)) {
            return null;
        }
        try {
            $envelope = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $payload = $envelope['payload'] ?? null;
            if (!is_array($payload)) {
                throw new RuntimeException('El resumen temporal no es válido.');
            }
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            throw new RuntimeException('El resumen temporal no es válido.');
        }
        if (!hash_equals((string) ($envelope['signature'] ?? ''), hash_hmac('sha256', $payloadJson, $this->signingKey()))
            || ($payload['token'] ?? null) !== $token
            || ($payload['user_id'] ?? null) !== $this->userId
            || !hash_equals((string) ($payload['session_hash'] ?? ''), hash('sha256', $this->sessionId))
            || (int) ($payload['expires_at'] ?? 0) < time()
        ) {
            throw new RuntimeException('La integridad del resumen temporal no pudo verificarse.');
        }
        return is_array($payload['result'] ?? null) ? $payload['result'] : null;
    }

    private function resultPath(array $entry): string
    {
        $files = $entry['files'] ?? [];
        $first = reset($files);
        if (!is_array($first) || !isset($first['path'])) {
            throw new RuntimeException('El lote no contiene los tres archivos requeridos.');
        }
        $directory = realpath(dirname((string) $first['path']));
        foreach ($files as $file) {
            if ($directory === false || realpath(dirname((string) ($file['path'] ?? ''))) !== $directory) {
                throw new RuntimeException('Los archivos del lote no comparten un directorio seguro.');
            }
        }
        return $directory . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    private function signingKey(): string
    {
        return hash('sha256', $this->sessionId . "\0" . $this->userId, true);
    }
}
