<?php
declare(strict_types=1);
namespace App\Auth;

final class Authorization
{
    public function __construct(private ?AuthenticatedUser $user) {}
    public function user(): ?AuthenticatedUser { return $this->user; }
    public function can(string $permission): bool { return $this->user?->can($permission) ?? false; }
    public function isSuperAdministrator(): bool
    {
        return $this->user?->isSuperAdministrator() ?? false;
    }
    public function require(string $permission): void
    {
        if ($this->user === null) throw new AuthenticationRequiredException('Debes iniciar sesión.');
        if (!$this->user->can($permission)) throw new ForbiddenException('No tienes permiso para realizar esta acción.');
    }
    public function requireSuperAdministrator(): void
    {
        if ($this->user === null) throw new AuthenticationRequiredException('Debes iniciar sesión.');
        if (!$this->user->isSuperAdministrator()) throw new ForbiddenException('Esta acción requiere privilegios de Super Administrador.');
    }
}
