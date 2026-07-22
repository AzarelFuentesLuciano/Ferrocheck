<?php
declare(strict_types=1);
namespace App\Auth;

final class Csrf
{
    private const KEY = '_auth_csrf';
    public function __construct(private array &$session) {}
    public function token(): string { return $this->session[self::KEY] ??= bin2hex(random_bytes(32)); }
    public function validate(string $token): bool
    {
        $stored = $this->session[self::KEY] ?? '';
        return is_string($stored) && $stored !== '' && $token !== '' && hash_equals($stored, $token);
    }
    public function rotate(): string { unset($this->session[self::KEY]); return $this->token(); }
}
