<?php
declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\AuthenticatedUser;
use App\Controllers\DashboardController;
use App\Support\AuthenticatedHeaderBuilder;

if(!defined('BASE_URL'))define('BASE_URL','/vascor-test');

function renderSharedHeader(AuthenticatedUser$user):string
{
    $escape=static fn(mixed$value):string=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
    $header=AuthenticatedHeaderBuilder::build($user,BASE_URL.'/index.php?modulo=auth&accion=logout','sentinel-global-csrf',[
        'systemSubtitle'=>'Plataforma Operativa',
    ]);
    ob_start();
    require dirname(__DIR__,2).'/app/Views/partials/header.php';
    return(string)ob_get_clean();
}

function renderDashboardAppShell(AuthenticatedUser$user):string
{
    $controller=new DashboardController();
    $controller->setAuthenticatedUser($user,'sentinel-dashboard-csrf');
    $method=new ReflectionMethod($controller,'renderAppShell');
    $method->setAccessible(true);
    return(string)$method->invoke($controller,'dashboard');
}

function renderInventoryLegacy(AuthenticatedUser$user):string
{
    $_GET=['modulo'=>'dashboard'];
    $_SESSION['auth_permissions']=['administracion.acceder'];
    $_SESSION['auth_module_keys']=[];
    $controller=new DashboardController();
    $controller->setAuthenticatedUser($user,'sentinel-inventory-csrf');
    $method=new ReflectionMethod($controller,'renderLegacy');
    $method->setAccessible(true);
    ob_start();
    $method->invoke($controller);
    return(string)ob_get_clean();
}

$permissions=['administracion.acceder'];
$super=new AuthenticatedUser(1,'Actor Sentinel','actor_sentinel',['Administrador'],$permissions,true,true);
$normal=new AuthenticatedUser(2,'Administrador Normal','admin_normal',['Administrador'],$permissions);
$adminHeader=renderSharedHeader($super);
$normalHeader=renderSharedHeader($normal);
$dashboardHeader=renderDashboardAppShell($super);
$inventoryHeader=renderInventoryLegacy($super);
$normalInventoryHeader=renderInventoryLegacy($normal);
$inventorySource=(string)file_get_contents(dirname(__DIR__,2).'/app/Views/inventario/importar.php');

test('helper conserva metadatos y fuerza identidad real',function()use($super){$header=AuthenticatedHeaderBuilder::build($super,'/logout','csrf',['systemSubtitle'=>'Operación','currentUser'=>'Falso','currentRole'=>'Falso','currentBadge'=>'Falso']);same('Operación',$header['systemSubtitle']);same('Actor Sentinel',$header['currentUser']);same('Administrador',$header['currentRole']);same('Super Administrador',$header['currentBadge']);});
test('helper usa Usuario cuando no hay roles',function(){$user=new AuthenticatedUser(3,'Sin Rol','sin_rol',[],[],true);same('Usuario',AuthenticatedHeaderBuilder::build($user,'/logout','csrf')['currentRole']);});
test('Administración Super Administrador renderiza una insignia',fn()=>ok(substr_count($adminHeader,'app-header-user__sentinel-badge')===1&&str_contains($adminHeader,'<small>Administrador</small>')));
test('Dashboard app-shell Super Administrador renderiza una insignia',fn()=>ok(substr_count($dashboardHeader,'app-header-user__sentinel-badge')===1&&str_contains($dashboardHeader,'<small>Administrador</small>')));
test('Inventario legacy Super Administrador renderiza una insignia',fn()=>ok(substr_count($inventoryHeader,'app-header-user__sentinel-badge')===1&&str_contains($inventoryHeader,'<small>Administrador</small>')));
test('Administrador normal no recibe insignia',fn()=>ok(!str_contains($normalHeader,'app-header-user__sentinel-badge')&&str_contains($normalHeader,'<small>Administrador</small>')));
test('Inventario legacy normal no recibe insignia',fn()=>ok(!str_contains($normalInventoryHeader,'app-header-user__sentinel-badge')&&str_contains($normalInventoryHeader,'<small>Administrador</small>')));
test('rol e insignia permanecen separados globalmente',fn()=>ok(!str_contains($adminHeader,'Administrador · Super Administrador')&&!str_contains($dashboardHeader,'Administrador · Super Administrador')&&!str_contains($inventoryHeader,'Administrador · Super Administrador')));
test('encabezados conservan controles compartidos',fn()=>ok(str_contains($adminHeader,'app-header-menu')&&str_contains($adminHeader,'app-header-logout')&&str_contains($dashboardHeader,'app-header-meta__version')&&str_contains($dashboardHeader,'data-app-shell-date')&&str_contains($dashboardHeader,'data-app-shell-time')));
test('Inventario conserva menú, logout y metadatos legacy',fn()=>ok(str_contains($inventoryHeader,'class="app-header-menu menu-toggle"')&&str_contains($inventoryHeader,'aria-controls="sidebarNav"')&&str_contains($inventoryHeader,'class="app-header-logout"')&&str_contains($inventoryHeader,'Versión v1.0')&&str_contains($inventoryHeader,'id="currentDate"')&&str_contains($inventoryHeader,'id="currentTime"')));
test('Inventario no lee identidad desde sesión',fn()=>ok(!preg_match("/\\\$_SESSION\\[['\"]auth_(?:name|username|roles|super_administrator)['\"]\\]/",$inventorySource)));

finish('Sentinel Global Header Phase B');
