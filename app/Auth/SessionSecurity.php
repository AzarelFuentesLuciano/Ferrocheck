<?php
declare(strict_types=1);
namespace App\Auth;

final class SessionSecurity
{
    public const LIFETIME = 28800;

    public static function start(array $server): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = ($server['HTTPS'] ?? '') !== '' && strtolower((string) $server['HTTPS']) !== 'off';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function fingerprint(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }
}
