<?php
declare(strict_types=1);

namespace App\Auth;

interface OrganizationalAccessRepositoryInterface
{
    public function findActiveModule(string $moduleKey): ?array;
    public function individualModuleDecision(int $userId, int $moduleId): ?string;
    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool;
    public function activeAreaIdsForUser(int $userId): array;
    public function activeAreaExists(int $areaId): bool;
    public function visibleActiveModules(): array;
    public function userHasActiveArea(int $userId): bool;
    public function userHasActiveModuleDecision(int $userId): bool;
}
