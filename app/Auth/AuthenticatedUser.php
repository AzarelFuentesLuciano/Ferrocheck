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
    ) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
