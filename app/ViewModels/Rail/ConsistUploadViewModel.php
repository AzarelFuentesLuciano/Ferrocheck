<?php

declare(strict_types=1);

namespace App\ViewModels\Rail;

final readonly class ConsistUploadViewModel
{
    public array $fileMessages;

    public function __construct(
        public string $csrfToken,
        public array $messages,
        public ?array $preview,
        public array $configuration,
        public string $batchToken = '',
        public bool $canAnalyze = false,
        public ?ConsistAnalysisViewModel $analysis = null,
    ) {
        $fileMessages = [];
        foreach ($messages as $message) {
            $field = isset($message['field']) && is_string($message['field'])
                ? trim($message['field'])
                : '';
            if ($field === '' || !isset($configuration['files'][$field])) {
                continue;
            }
            $fileMessages[$field][] = [
                'type' => (string) ($message['type'] ?? 'error'),
                'message' => self::cardMessage((string) ($message['message'] ?? '')),
            ];
        }
        $this->fileMessages = $fileMessages;
    }

    private static function cardMessage(string $message): string
    {
        if (preg_match('/parece ser un archivo “([^”]+)”/u', $message, $match) === 1) {
            return sprintf('El archivo corresponde a %s.', $match[1]);
        }
        if (str_contains($message, 'no coincide con ninguna estructura reconocida')) {
            return 'La estructura del archivo no es reconocida.';
        }
        if (str_contains($message, 'identificar de forma segura')) {
            return 'No fue posible identificar el tipo del archivo.';
        }

        return preg_replace('/^No se pudo validar el lote\.\s*/u', '', $message) ?? $message;
    }
}
