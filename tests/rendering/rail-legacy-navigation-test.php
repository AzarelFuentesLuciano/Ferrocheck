<?php

declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\AuthenticatedUser;
use App\Auth\OrganizationalAccess;
use App\Auth\OrganizationalAccessRepositoryInterface;
use App\Controllers\DashboardController;
use App\Services\ModuleNavigationBuilder;

final class RailLegacyNavigationRepository implements OrganizationalAccessRepositoryInterface
{
    public array $decisions = [];

    private array $modules = [
        'dashboard'=>['id'=>1,'clave'=>'dashboard','nombre'=>'Dashboard','ruta'=>'dashboard','icono'=>'D','orden'=>10],
        'rail'=>['id'=>2,'clave'=>'rail','nombre'=>'Rail','ruta'=>'rail','icono'=>'R','orden'=>15],
        'ferrocheck'=>['id'=>3,'clave'=>'ferrocheck','nombre'=>'FerroCheck','ruta'=>'ferrocheck','icono'=>'F','orden'=>20],
        'inventario_material'=>['id'=>4,'clave'=>'inventario_material','nombre'=>'Inventario de Material','ruta'=>'inventario-material','icono'=>'M','orden'=>30],
        'inventario_patio'=>['id'=>5,'clave'=>'inventario_patio','nombre'=>'Inventario de Patio','ruta'=>'operaciones-patio','icono'=>'P','orden'=>40],
        'control_escaneres'=>['id'=>6,'clave'=>'control_escaneres','nombre'=>'Control de Escáneres','ruta'=>'control-escaneres','icono'=>'E','orden'=>50],
        'reportes'=>['id'=>7,'clave'=>'reportes','nombre'=>'Reportes','ruta'=>'reportes','icono'=>'I','orden'=>60],
        'configuracion_general'=>['id'=>8,'clave'=>'configuracion_general','nombre'=>'Configuración General','ruta'=>'configuracion-general','icono'=>'C','orden'=>80],
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

function railNavigationBuilder(RailLegacyNavigationRepository $repository): ModuleNavigationBuilder
{
    $user = new AuthenticatedUser(1, 'Usuario Rail', 'rail_user', [], ['modulos.acceso_global']);
    return new ModuleNavigationBuilder(new OrganizationalAccess($user, $repository));
}

function findNavigationModule(array $modules, string $key): ?array
{
    foreach ($modules as $module) {
        if (($module['key'] ?? null) === $key) {
            return $module;
        }
    }
    return null;
}

function renderLegacySidebar(array $modules, string $activeModule, string $activeSection = ''): string
{
    $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    ob_start();
    require dirname(__DIR__, 2) . '/app/Views/partials/legacy-sidebar.php';
    return (string) ob_get_clean();
}

$repository = new RailLegacyNavigationRepository();
$builder = railNavigationBuilder($repository);
$navigation = $builder->build('/Ferrocheck/public');
$rail = findNavigationModule($navigation, 'rail');

test('FerroCheck no aparece como módulo principal', fn()=>ok(!in_array('ferrocheck',array_column($navigation,'key'),true)));
test('sidebar principal no agrega FerroCheck como submenú de Rail',fn()=>same([],$rail['sections']??null));
test('módulo Rail abre directamente la URL histórica de FerroCheck',fn()=>same(
    '/Ferrocheck/public/index.php?modulo=ferrocheck&seccion=dashboard',
    $rail['url']??null
));
test('URL histórica de FerroCheck permanece en la navegación interna',function():void{
    $configuration=require dirname(__DIR__,2).'/config/rail-navigation.php';
    same('index.php?modulo=ferrocheck&seccion=dashboard',$configuration['ferrocheck']['url']??null);
});
test('orden principal conserva el catálogo sin depender de posiciones fijas',fn()=>same(
    ['dashboard','rail','inventario_material','inventario_patio','control_escaneres','reportes','configuracion_general'],
    array_column($navigation,'key')
));

$repository->decisions[3]='denegar';
$withoutFerro=$builder->build('/Ferrocheck/public');
test('Rail autorizado y FerroCheck denegado deja Rail sin FerroCheck',fn()=>same([],findNavigationModule($withoutFerro,'rail')['sections']??null));

$repository->decisions=[2=>'denegar'];
$withoutRail=$builder->build('/Ferrocheck/public');
test('Rail denegado evita FerroCheck principal o huérfano',fn()=>ok(
    findNavigationModule($withoutRail,'rail')===null&&!in_array('ferrocheck',array_column($withoutRail,'key'),true)
));

$repository->decisions=[2=>'denegar',3=>'denegar'];
$withoutBoth=$builder->build('/Ferrocheck/public');
test('ambos denegados no aparecen',fn()=>ok(
    findNavigationModule($withoutBoth,'rail')===null&&!in_array('ferrocheck',array_column($withoutBoth,'key'),true)
));

$legacyHtml=renderLegacySidebar($navigation,'rail','ferrocheck');
test('bridge conserva hooks responsive legacy',fn()=>ok(
    str_contains($legacyHtml,'id="sidebarNav"')
    &&str_contains($legacyHtml,'class="sidebar"')
    &&str_contains($legacyHtml,'sidebar__item')
    &&str_contains($legacyHtml,'sidebar__icon')
    &&str_contains($legacyHtml,'sidebar__text')
    &&!str_contains($legacyHtml,'sidebar-submenu')
));
test('bridge renderiza Rail activo sin submenús',fn()=>ok(
    preg_match('/sidebar__item[^"]* active[^>]+data-label="Rail"/',$legacyHtml)===1
    &&!str_contains($legacyHtml,'FerroCheck')
));
test('bridge soporta módulos futuros sin catálogo manual',function()use($navigation):void{
    $future=$navigation;
    $future[]=['id'=>'futuro','key'=>'futuro','label'=>'Módulo Futuro','url'=>'/futuro','icon'=>'N','sections'=>[]];
    ok(str_contains(renderLegacySidebar($future,'futuro'),'Módulo Futuro'));
});

$controller=new DashboardController();
$controller->setAuthenticatedUser(
    new AuthenticatedUser(1,'Usuario Rail','rail_user',['Administrador'],['modulos.acceso_global']),
    'csrf',
    railNavigationBuilder(new RailLegacyNavigationRepository())
);
$contextMethod=new ReflectionMethod($controller,'buildLegacyRenderData');
$navigationMethod=new ReflectionMethod($controller,'renderRailNavigationForFerroCheck');
$railModuleNavigation=$navigationMethod->invoke($controller);
$ferroContext=$contextMethod->invoke($controller,'<section id="ferro-content"></section>','consulta-vin',$railModuleNavigation);
test('FerroCheck conserva sección funcional y activa Rail en App Shell',fn()=>ok(
    $ferroContext['modulo']==='rail'
    &&$ferroContext['seccion']==='ferrocheck'
    &&str_contains($ferroContext['contenidoModulo'],'ferro-content')
    &&str_contains($ferroContext['moduleNavigation'],'class="rail-navigation"')
    &&str_contains($ferroContext['moduleNavigation'],'rail-primary-nav__link--current')
    &&substr_count($ferroContext['moduleNavigation'],'class="rail-navigation"')===1
    &&str_contains(implode('|',$ferroContext['additionalStyles']),'/assets/css/rail/rail.css')
    &&str_contains(implode('|',$ferroContext['additionalStyles']),'/assets/css/importador.css')
    &&str_contains($ferroContext['additionalScripts'][0],'/assets/js/importador.js')
));
test('título general precede la navegación interna de FerroCheck',fn()=>ok(
    strpos($ferroContext['moduleNavigation'],'<h1>Rail</h1>')
    < strpos($ferroContext['moduleNavigation'],'class="rail-navigation"')
));

$root=dirname(__DIR__,2);
$inventorySource=(string)file_get_contents($root.'/app/Views/inventario/importar.php');
$patioSource=(string)file_get_contents($root.'/app/Views/operaciones-patio/operaciones-patio.php');
$legacySource=(string)file_get_contents($root.'/app/Views/partials/legacy-sidebar.php');
$scannerSource=(string)file_get_contents($root.'/app/Controllers/ControlEscaneres/ControlEscaneresWebController.php');
$dashboardSource=(string)file_get_contents($root.'/app/Controllers/DashboardController.php');
$publicSource=(string)file_get_contents($root.'/public/index.php');
$ferroContent=(string)file_get_contents($root.'/app/Views/inventario/partials/ferrocheck-content.php');
test('vistas migradas no consumen auth_module_keys ni catálogos manuales',fn()=>ok(
    !str_contains($inventorySource,'auth_module_keys')
    &&!str_contains($patioSource,'auth_module_keys')
    &&str_contains($inventorySource,'legacy-sidebar.php')
    &&str_contains($patioSource,'legacy-sidebar.php')
    &&substr_count($legacySource,'foreach ($modules as $module)')===1
));
test('Control de Escáneres prepara ambos renders de importar.php',fn()=>ok(
    substr_count($scannerSource,"/Views/inventario/importar.php")===2
    &&substr_count($scannerSource,'$this->legacyNavigation(')===2
));
test('Dashboard elimina catálogo manual y conserva endpoint histórico',fn()=>ok(
    !str_contains($dashboardSource,"'id' => 'ferrocheck'")
    &&str_contains($publicSource,"(\$_GET['modulo'] ?? '') === 'rail'")
    &&str_contains($dashboardSource,'renderRailNavigationForFerroCheck')
    &&str_contains($ferroContent,'id="importador"')
    &&str_contains($ferroContent,'name="archivo"')
));
test('auth_module_keys deja de ser fuente de navegación activa',fn()=>ok(
    !str_contains($publicSource,'auth_module_keys')
    &&!str_contains($inventorySource,'auth_module_keys')
    &&!str_contains($patioSource,'auth_module_keys')
));

finish('Rail Legacy Navigation');
