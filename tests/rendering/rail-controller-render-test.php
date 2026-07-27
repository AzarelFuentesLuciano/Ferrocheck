<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Auth\AuthenticatedUser;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Controllers\Rail\RailController;
use App\Services\ModuleNavigationBuilder;

final class RailControllerAccessRepository implements OrganizationalAccessRepositoryInterface
{
    public function findActiveModule(string $moduleKey): ?array
    {
        foreach ($this->visibleActiveModules() as $module) {
            if ($module['clave'] === $moduleKey) {
                return $module;
            }
        }
        return null;
    }

    public function individualModuleDecision(int $userId, int $moduleId): ?string
    {
        return null;
    }

    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool
    {
        return true;
    }

    public function activeAreaIdsForUser(int $userId): array
    {
        return [1];
    }

    public function activeAreaExists(int $areaId): bool
    {
        return $areaId === 1;
    }

    public function visibleActiveModules(): array
    {
        return [
            ['id'=>1,'clave'=>'dashboard','nombre'=>'Dashboard','descripcion'=>'','ruta'=>'dashboard','icono'=>'⌂','orden'=>10],
            ['id'=>2,'clave'=>'rail','nombre'=>'Rail','descripcion'=>'','ruta'=>'rail','icono'=>'▰','orden'=>15],
            ['id'=>3,'clave'=>'ferrocheck','nombre'=>'FerroCheck','descripcion'=>'','ruta'=>'ferrocheck','icono'=>'F','orden'=>20],
        ];
    }

    public function userHasActiveArea(int $userId): bool
    {
        return true;
    }

    public function userHasActiveModuleDecision(int $userId): bool
    {
        return false;
    }
}

$passed = 0;
$failed = 0;
$test = static function (string $name, callable $assertion) use (&$passed, &$failed): void {
    try {
        $result = $assertion() === true;
    } catch (Throwable $exception) {
        $result = false;
        $name .= ' (' . $exception::class . ': ' . $exception->getMessage() . ')';
    }
    $result ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $result ? 'PASS' : 'FAIL', $name);
};

$user = new AuthenticatedUser(10, 'Administrador Rail', 'admin_rail', ['Administrador'], ['rail.ver']);
$access = new OrganizationalAccess($user, new RailControllerAccessRepository());
$controller = new RailController($user, 'csrf-rail', new ModuleNavigationBuilder($access), null, '/Ferrocheck/public');
$html = $controller->render(['seccion'=>'ferrocheck','subseccion'=>'incidencias']);
$fallback = $controller->render(['seccion'=>'../../invalid','subseccion'=>'../../invalid']);

$test('controlador renderiza un solo App Shell', static fn (): bool => preg_match_all('/<html\b/i', $html) === 1 && substr_count($html, 'data-app-shell>') === 1);
$test('Rail queda activo en el sidebar', static fn (): bool => preg_match('/app-sidebar-link is-active[^>]+href="[^"]*modulo=rail"/', $html) === 1);
$test('Rail contiene FerroCheck como submenú', static fn (): bool => str_contains($html, 'appShellSubmenu-rail') && str_contains($html, 'modulo=ferrocheck&amp;seccion=dashboard'));
$test('navegación interna contiene exactamente dos barras', static fn (): bool => substr_count($html, '<nav class="rail-') === 2);
$test('navegación interna no se duplica', static fn (): bool => substr_count($html, 'class="rail-navigation"') === 1);
$test('moduleNavigation aparece antes del contenido', static fn (): bool => strpos($html, 'class="rail-navigation"') < strpos($html, 'class="rail-module"'));
$test('sección y subsección quedan activas', static fn (): bool => str_contains($html, '<h1 id="railSectionTitle">FerroCheck</h1>') && str_contains($html, '<strong>Incidencias</strong>'));
$test('sección inválida cae en Dashboard resumen', static fn (): bool => str_contains($fallback, '<h1 id="railSectionTitle">Dashboard</h1>') && str_contains($fallback, '<strong>Resumen</strong>'));
$test('CSS Rail se carga una sola vez después del CSS global', static fn (): bool => substr_count($html, '/assets/css/rail/rail.css') === 1 && strpos($html, '/assets/css/app-shell.css') < strpos($html, '/assets/css/rail/rail.css'));
$test('Rail no carga JavaScript propio', static fn (): bool => !str_contains($html, '/assets/js/rail/'));
$test('encabezado conserva identidad y logout', static fn (): bool => str_contains($html, 'Administrador Rail') && str_contains($html, 'csrf-rail') && str_contains($html, 'modulo=auth&amp;accion=logout'));

echo "\nResumen Rail Controller Render: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
