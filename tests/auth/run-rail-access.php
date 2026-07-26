<?php

declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\AuthenticatedUser;
use App\Auth\ForbiddenException;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Services\ModuleNavigationBuilder;

final class RailAccessRepository implements OrganizationalAccessRepositoryInterface
{
    public bool $inherits = false;

    public function findActiveModule(string $moduleKey): ?array
    {
        return $moduleKey === 'rail'
            ? ['id'=>15,'clave'=>'rail','nombre'=>'Rail','ruta'=>'rail','icono'=>'▰','orden'=>15]
            : null;
    }

    public function individualModuleDecision(int $userId, int $moduleId): ?string { return null; }
    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool { return $this->inherits; }
    public function activeAreaIdsForUser(int $userId): array { return $this->inherits ? [1] : []; }
    public function activeAreaExists(int $areaId): bool { return $areaId === 1; }
    public function visibleActiveModules(): array { return [$this->findActiveModule('rail')]; }
    public function userHasActiveArea(int $userId): bool { return $this->inherits; }
    public function userHasActiveModuleDecision(int $userId): bool { return false; }
}

$repository = new RailAccessRepository();
$authorizedUser = new AuthenticatedUser(1, 'Administrador Sistemas', 'admin', ['Administrador'], ['rail.ver']);
$authorizedAccess = new OrganizationalAccess($authorizedUser, $repository);
$repository->inherits = true;

test('usuario con área Sistemas y rail.ver abre Rail', function () use ($authorizedAccess): void {
    $authorizedAccess->requireModuleAccess('rail', 'rail.ver');
    ok(true);
});
test('Rail aparece en navegación autorizada sin subsecciones', function () use ($authorizedAccess): void {
    $items = (new ModuleNavigationBuilder($authorizedAccess))->build('/Ferrocheck/public');
    same(1, count($items));
    same('rail', $items[0]['key']);
    same([], $items[0]['sections']);
});

$repository->inherits = false;
$withoutOrganization = new OrganizationalAccess($authorizedUser, $repository);
test('rail.ver sin acceso organizacional recibe 403', function () use ($withoutOrganization): void {
    try {
        $withoutOrganization->requireModuleAccess('rail', 'rail.ver');
    } catch (ForbiddenException) {
        ok(true);
        return;
    }
    throw new RuntimeException('Se autorizó Rail sin acceso organizacional.');
});
test('Rail no aparece sin acceso organizacional', fn () => same([], (new ModuleNavigationBuilder($withoutOrganization))->build('/Ferrocheck/public')));

$repository->inherits = true;
$withoutPermission = new OrganizationalAccess(
    new AuthenticatedUser(2, 'Usuario Sistemas', 'sistemas', ['Consulta'], []),
    $repository,
);
test('acceso organizacional sin rail.ver recibe 403', function () use ($withoutPermission): void {
    try {
        $withoutPermission->requireModuleAccess('rail', 'rail.ver');
    } catch (ForbiddenException) {
        ok(true);
        return;
    }
    throw new RuntimeException('Se autorizó Rail sin rail.ver.');
});

$publicSource = (string) file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
$guardPosition = strpos($publicSource, 'if ($currentUser === null)');
$railPosition = strpos($publicSource, "if ((\$_GET['modulo'] ?? '') === 'rail')");
test('usuario no autenticado pasa por login antes de Rail', fn () => ok($guardPosition !== false && $railPosition !== false && $guardPosition < $railPosition));
test('rama pública exige módulo y permiso', fn () => ok(str_contains($publicSource, "requireModuleAccess('rail', 'rail.ver')")));

finish('Rail Access');
