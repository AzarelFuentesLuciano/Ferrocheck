<?php
declare(strict_types=1);
namespace App\Auth;

final readonly class AuthenticatedUser
{
    public function __construct(
        public int $id,
        public string $name,
        public string $username,
        public array $roles,
        public array $permissions,
        public bool $superAdministrator = false,
        public bool $protectedUser = false,
    ) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function isSuperAdministrator(): bool
    {
        return $this->superAdministrator;
    }

    public function isProtectedUser(): bool
    {
        return $this->protectedUser;
    }
}
