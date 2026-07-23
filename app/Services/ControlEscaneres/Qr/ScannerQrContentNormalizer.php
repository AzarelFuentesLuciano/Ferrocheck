<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Qr;

final class ScannerQrContentNormalizer
{
    private const PUBLIC_HOST = 'vascor.figurbox.net';

    public function normalize(string $content): array
    {
        $content = trim(preg_replace('/[\r\n\t]+/u', '', $content) ?? '');
        if ($content === '' || mb_strlen($content) > 2048) {
            throw new \InvalidArgumentException('Formato QR inválido.');
        }
        if (!str_starts_with($content, '/') && !preg_match('#^https?://#i', $content)) {
            return ['identifier' => $content, 'scanner_id' => null, 'legacy' => false];
        }
        $url = str_starts_with($content, '/') ? 'https://' . self::PUBLIC_HOST . $content : $content;
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host']) || !$this->allowedHost((string) $parts['host'])) {
            throw new \InvalidArgumentException('El QR pertenece a un origen no permitido.');
        }
        parse_str((string) ($parts['query'] ?? ''), $query);
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        if (!str_ends_with($path, '/index.php') || (($query['modulo'] ?? 'control-escaneres') !== 'control-escaneres')) {
            throw new \InvalidArgumentException('La ruta del QR no pertenece a Control de Escáneres.');
        }
        foreach (['codigo', 'codigo_qr'] as $key) {
            $value = trim((string) ($query[$key] ?? ''));
            if ($value !== '') return ['identifier' => $value, 'scanner_id' => null, 'legacy' => false];
        }
        $scannerId = filter_var($query['scanner_id'] ?? null, FILTER_VALIDATE_INT);
        if ($scannerId !== false && $scannerId > 0) {
            return ['identifier' => null, 'scanner_id' => $scannerId, 'legacy' => true];
        }
        throw new \InvalidArgumentException('El QR no contiene un identificador reconocido.');
    }

    public static function canonicalUrl(string $identifier): string
    {
        return 'https://' . self::PUBLIC_HOST . '/index.php?modulo=control-escaneres&accion=resolver-qr&codigo=' . rawurlencode($identifier);
    }

    private function allowedHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if (in_array($host, [self::PUBLIC_HOST, 'localhost', '127.0.0.1', '::1'], true)) return true;
        if (filter_var($host, FILTER_VALIDATE_IP) === false) return false;
        return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
