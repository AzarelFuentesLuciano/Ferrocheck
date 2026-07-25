<?php
declare(strict_types=1);

namespace App\Support;

use App\Auth\AuthenticatedUser;

final class AuthenticatedHeaderBuilder
{
    /**
     * Construye un encabezado con currentUser, currentRole, currentBadge,
     * logoutUrl y logoutCsrf como claves mínimas.
     *
     * La identidad derivada de AuthenticatedUser prevalece siempre sobre
     * cualquier clave homónima recibida en $metadata. Cuando roles está vacío,
     * currentRole usa el valor compatible "Usuario".
     */
    public static function build(
        AuthenticatedUser $user,
        string $logoutUrl,
        string $logoutCsrf,
        array $metadata = [],
    ): array {
        return array_replace($metadata, [
            'currentUser' => $user->name,
            'currentRole' => $user->roles[0] ?? 'Usuario',
            'currentBadge' => $user->isSuperAdministrator() ? 'Super Administrador' : '',
            'logoutUrl' => $logoutUrl,
            'logoutCsrf' => $logoutCsrf,
        ]);
    }
}
