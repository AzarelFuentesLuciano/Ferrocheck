<?php
declare(strict_types=1);

namespace App\Services;

use App\Auth\OrganizationalAccess;

final class ModuleNavigationBuilder
{
    public function __construct(private OrganizationalAccess $access) {}

    public function build(string $baseUrl, array $sectionsByModule = []): array
    {
        $modules = [];
        foreach ($this->access->authorizedModules() as $module) {
            $key = (string) $module['clave'];
            $route = ltrim((string) $module['ruta'], '/');
            $modules[] = [
                'id' => str_replace('_', '-', $key),
                'key' => $key,
                'label' => (string) $module['nombre'],
                'url' => rtrim($baseUrl, '/') . '/index.php?modulo=' . rawurlencode($route),
                'icon' => (string) ($module['icono'] ?? '•'),
                'sections' => $sectionsByModule[$key] ?? [],
            ];
        }
        return $modules;
    }
}

