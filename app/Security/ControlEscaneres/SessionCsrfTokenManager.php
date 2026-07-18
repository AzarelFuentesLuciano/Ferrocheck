<?php
declare(strict_types=1);
namespace App\Security\ControlEscaneres;
final class SessionCsrfTokenManager implements CsrfTokenManagerInterface
{
    private const KEY = '_ce_csrf';
    public function __construct(private array &$session) {}
    public function token(): string { return $this->session[self::KEY] ??= bin2hex(random_bytes(32)); }
    public function validate(string $token): bool { $stored=$this->session[self::KEY]??''; return $stored!==''&&$token!==''&&hash_equals($stored,$token); }
    public function rotate(): string { unset($this->session[self::KEY]); return $this->token(); }
}
