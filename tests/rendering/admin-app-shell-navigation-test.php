<?php

declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\AuthenticatedUser;
use App\Auth\Authorization;
use App\Auth\ForbiddenException;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Controllers\AdministrationController;
use App\Services\ModuleNavigationBuilder;

final class AdminNavigationRepository implements OrganizationalAccessRepositoryInterface
{
    public array $decisions = [];

    private array $modules = [
        'dashboard' => ['id'=>1,'clave'=>'dashboard','nombre'=>'Dashboard','ruta'=>'dashboard','icono'=>'D','orden'=>10],
        'rail' => ['id'=>2,'clave'=>'rail','nombre'=>'Rail','ruta'=>'rail','icono'=>'R','orden'=>15],
        'administracion' => ['id'=>3,'clave'=>'administracion','nombre'=>'Administración','ruta'=>'administracion','icono'=>'A','orden'=>70],
    ];

    public function findActiveModule(string $moduleKey): ?array { return $this->modules[$moduleKey] ?? null; }
    public function individualModuleDecision(int $userId, int $moduleId): ?string { return $this->decisions[$moduleId] ?? null; }
    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool { return false; }
    public function activeAreaIdsForUser(int $userId): array { return []; }
    public function activeAreaExists(int $areaId): bool { return false; }
    public function visibleActiveModules(): array { return array_values($this->modules); }
    public function userHasActiveArea(int $userId): bool { return false; }
    public function userHasActiveModuleDecision(int $userId): bool { return $this->decisions !== []; }
}

function adminNavigation(
    AuthenticatedUser $user,
    AdminNavigationRepository $repository,
    string $baseUrl = '/Ferrocheck/public'
): array {
    $controller = (new ReflectionClass(AdministrationController::class))->newInstanceWithoutConstructor();
    $authorization = new ReflectionProperty(AdministrationController::class, 'authorization');
    $authorization->setValue($controller, new Authorization($user));
    $builder = new ReflectionProperty(AdministrationController::class, 'moduleNavigationBuilder');
    $builder->setValue($controller, new ModuleNavigationBuilder(new OrganizationalAccess($user, $repository)));
    $method = new ReflectionMethod(AdministrationController::class, 'buildNavigation');

    return $method->invoke($controller, $baseUrl);
}

$allPermissions = [
    'administracion.acceder',
    'usuarios.ver',
    'roles.ver',
    'areas.ver',
    'modulos.ver',
    'modulos.acceso_global',
];
$repository = new AdminNavigationRepository();
$authorizedUser = new AuthenticatedUser(1, 'Administración', 'admin', [], $allPermissions);
$navigation = adminNavigation($authorizedUser, $repository);

test('Administración recibe navegación dinámica en orden del catálogo', function () use ($navigation): void {
    same(['dashboard','rail','administracion'], array_column($navigation, 'key'));
});
test('Rail aparece en Administración cuando está autorizado', function () use ($navigation): void {
    ok(in_array('rail', array_column($navigation, 'key'), true));
});
test('Administración conserva URL y subsecciones actuales', function () use ($navigation): void {
    $admin = $navigation[2];
    same('/Ferrocheck/public/index.php?modulo=administracion&seccion=usuarios', $admin['url']);
    same(['usuarios','roles','areas','modulos'], array_column($admin['sections'], 'id'));
});

$repository->decisions[2] = 'denegar';
$deniedNavigation = adminNavigation($authorizedUser, $repository);
test('Rail desaparece de Administración cuando está denegado', function () use ($deniedNavigation): void {
    ok(!in_array('rail', array_column($deniedNavigation, 'key'), true));
});

$limitedPermissions = ['administracion.acceder','usuarios.ver','areas.ver','modulos.acceso_global'];
$limitedUser = new AuthenticatedUser(2, 'Administración limitada', 'admin_limitado', [], $limitedPermissions);
$limitedNavigation = adminNavigation($limitedUser, new AdminNavigationRepository());
test('subsecciones de Administración respetan permisos', function () use ($limitedNavigation): void {
    same(['usuarios','areas'], array_column($limitedNavigation[2]['sections'], 'id'));
});
test('Administración sin subsecciones conserva cierre por 403', function (): void {
    $user = new AuthenticatedUser(3, 'Sin subsecciones', 'sin_secciones', [], [
        'administracion.acceder',
        'modulos.acceso_global',
    ]);
    try {
        adminNavigation($user, new AdminNavigationRepository());
    } catch (ForbiddenException) {
        ok(true);
        return;
    }
    throw new RuntimeException('Administración generó navegación sin subsecciones autorizadas.');
});

$root = dirname(__DIR__, 2);
$layout = (string) file_get_contents($root . '/app/Views/admin/_layout.php');
$controller = (string) file_get_contents($root . '/app/Controllers/AdministrationController.php');
$public = (string) file_get_contents($root . '/public/index.php');
$adminViews = '';
foreach (['users.php','roles.php','areas.php','modules.php'] as $view) {
    $adminViews .= (string) file_get_contents($root . '/app/Views/admin/' . $view);
}

test('layout administrativo no conserva catálogo ni filtro manual', function () use ($layout): void {
    ok(
        !str_contains($layout, 'keyById')
        && !str_contains($layout, 'auth_module_keys')
        && !str_contains($layout, "\$modules = [")
        && str_contains($layout, '$modules = $navigationModules;')
    );
});
test('controlador usa ModuleNavigationBuilder y no consulta sesión de módulos', function () use ($controller): void {
    ok(
        str_contains($controller, '$this->moduleNavigationBuilder->build(')
        && !str_contains($controller, 'auth_module_keys')
    );
});
test('public index crea una sola instancia y la reutiliza', function () use ($public): void {
    same(1, substr_count($public, 'new ModuleNavigationBuilder('));
    ok(
        str_contains($public, '$moduleNavigationBuilder->build(')
        && substr_count($public, '$moduleNavigationBuilder,') >= 2
    );
});
test('Administración queda activa en el App Shell compartido', function () use ($layout): void {
    ok(
        str_contains($layout, "\$activeModule = 'administracion';")
        && str_contains($layout, "require dirname(__DIR__).'/layouts/app.php';")
    );
});
test('formularios y acciones administrativas permanecen presentes', function () use ($adminViews): void {
    foreach (['operation','_csrf','usuarios','roles','areas','modulos'] as $contract) {
        ok(str_contains($adminViews, $contract));
    }
});

finish('Admin App Shell Navigation');
