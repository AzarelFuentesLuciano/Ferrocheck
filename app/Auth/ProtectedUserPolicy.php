<?php
declare(strict_types=1);
namespace App\Auth;

final class ProtectedUserPolicy
{
    public function canView(AuthenticatedUser $actor, array|object $target): bool
    {
        return !$this->isProtected($target) || $actor->isSuperAdministrator();
    }

    public function canManage(AuthenticatedUser $actor, array|object $target): bool
    {
        return $this->canView($actor, $target);
    }

    public function assertCanView(AuthenticatedUser $actor, array|object $target): void
    {
        $this->assertProtectionStatusAvailable($target);
        if (!$this->canView($actor, $target)) {
            throw new ForbiddenException('El usuario solicitado no está disponible.');
        }
    }

    public function assertCanManage(AuthenticatedUser $actor, array|object $target): void
    {
        $this->assertProtectionStatusAvailable($target);
        if (!$this->canManage($actor, $target)) {
            throw new ForbiddenException('No tienes autorización para administrar esta cuenta.');
        }
    }

    private function assertProtectionStatusAvailable(array|object $target): void
    {
        if ($target instanceof AuthenticatedUser || (is_object($target) && method_exists($target, 'isProtectedUser'))) {
            return;
        }
        if (is_array($target) && array_key_exists('es_usuario_protegido', $target)) {
            return;
        }
        if (is_object($target) && array_key_exists('es_usuario_protegido', get_object_vars($target))) {
            return;
        }
        throw new ForbiddenException('No fue posible verificar la proteccion de la cuenta.');
    }

    private function isProtected(array|object $target): bool
    {
        if ($target instanceof AuthenticatedUser) return $target->isProtectedUser();
        if (is_array($target)) return $this->flag($target['es_usuario_protegido'] ?? false);
        if (method_exists($target, 'isProtectedUser')) return $this->flag($target->isProtectedUser());
        return $this->flag(get_object_vars($target)['es_usuario_protegido'] ?? false);
    }

    private function flag(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }
}
