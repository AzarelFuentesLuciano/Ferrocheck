<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

final class ConsistHeaderNormalizer
{
    public function normalize(mixed $value): string
    {
        $header = str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], trim((string) $value));
        $header = mb_strtolower($header, 'UTF-8');
        $header = strtr($header, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
        ]);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        $header = is_string($transliterated) ? $transliterated : $header;
        $header = preg_replace('/[^a-z0-9\/]+/', ' ', $header) ?? '';

        return trim(preg_replace('/\s+/', ' ', $header) ?? '');
    }
}
