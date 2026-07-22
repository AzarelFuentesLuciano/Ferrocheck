<?php
declare(strict_types=1);

namespace App\Auth;

final class OrganizationalAccess
{
    public function __construct(
        private AuthenticatedUser $user,
        private OrganizationalAccessRepositoryInterface $repository,
    ) {}

    public function canAccessModule(string $moduleKey): bool
    {
        $module = $this->repository->findActiveModule($moduleKey);
        if ($module === null) {
            return false;
        }

        $decision = $this->repository->individualModuleDecision($this->user->id, (int) $module['id']);
        if ($decision === 'denegar') {
            return false;
        }
        if ($this->user->can('modulos.acceso_global')) {
            return true;
        }
        if ($decision === 'permitir') {
            return true;
        }
        if ($this->repository->inheritsModuleFromActiveArea($this->user->id, (int) $module['id'])) {
            return true;
        }
        // Backfill cerrado: un usuario sin área y sin decisión explícita no hereda módulos.
        return false;
    }

    public function requireModuleAccess(string $moduleKey, ?string $requiredPermission = null): void
    {
        if (!$this->canAccessModule($moduleKey)) {
            throw new ForbiddenException('No tienes acceso a este módulo.');
        }
        if ($requiredPermission !== null && !$this->user->can($requiredPermission)) {
            throw new ForbiddenException('No tienes permiso para realizar esta acción.');
        }
    }

    public function authorizedAreaIds(): array
    {
        return $this->repository->activeAreaIdsForUser($this->user->id);
    }

    public function canAccessArea(int $areaId): bool
    {
        if ($areaId <= 0 || !$this->repository->activeAreaExists($areaId)) {
            return false;
        }
        if ($this->user->can('areas.acceso_global')) {
            return true;
        }
        return in_array($areaId, $this->authorizedAreaIds(), true);
    }

    public function requireAreaAccess(int $areaId, ?string $requiredPermission = null): void
    {
        if (!$this->canAccessArea($areaId)) {
            throw new ForbiddenException('No tienes alcance sobre el área solicitada.');
        }
        if ($requiredPermission !== null && !$this->user->can($requiredPermission)) {
            throw new ForbiddenException('No tienes permiso para realizar esta acción.');
        }
    }

    public function authorizedModules(): array
    {
        return array_values(array_filter(
            $this->repository->visibleActiveModules(),
            fn(array $module): bool => $this->canAccessModule((string) $module['clave']),
        ));
    }
}
