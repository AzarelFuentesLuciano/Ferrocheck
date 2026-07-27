<?php
declare(strict_types=1);

namespace App\Services;

use App\Auth\OrganizationalAccess;

final class ModuleNavigationBuilder
{
    public function __construct(private OrganizationalAccess $access) {}

    public function build(string $baseUrl, array $sectionsByModule = []): array
    {
        $authorizedModules = $this->access->authorizedModules();

        $modules = [];
        foreach ($authorizedModules as $module) {
            $key = (string) $module['clave'];
            if ($key === 'ferrocheck') {
                continue;
            }

            $route = ltrim((string) $module['ruta'], '/');
            $sections = $sectionsByModule[$key] ?? [];
            $url = $key === 'rail'
                ? rtrim($baseUrl, '/') . '/index.php?modulo=ferrocheck&seccion=dashboard'
                : rtrim($baseUrl, '/') . '/index.php?modulo=' . rawurlencode($route);

            $modules[] = [
                'id' => str_replace('_', '-', $key),
                'key' => $key,
                'label' => (string) $module['nombre'],
                'url' => $url,
                'icon' => (string) ($module['icono'] ?? '•'),
                'sections' => $sections,
            ];
        }
        return $modules;
    }
}
