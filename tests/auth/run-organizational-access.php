<?php
declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\{AuthenticatedUser,ForbiddenException,OrganizationalAccess,OrganizationalAccessRepositoryInterface};
use App\Services\ModuleNavigationBuilder;

final class InMemoryOrganizationalAccessRepository implements OrganizationalAccessRepositoryInterface
{
    public array $modules = [
        'dashboard'=>['id'=>1,'clave'=>'dashboard','nombre'=>'Dashboard','ruta'=>'dashboard','icono'=>'D','orden'=>10],
        'control_escaneres'=>['id'=>2,'clave'=>'control_escaneres','nombre'=>'Control de Escáneres','ruta'=>'control-escaneres','icono'=>'E','orden'=>20],
        'administracion'=>['id'=>3,'clave'=>'administracion','nombre'=>'Administración','ruta'=>'administracion','icono'=>'A','orden'=>30],
    ];
    public array $decisions=[];
    public array $inherited=[];
    public array $userAreas=[7];
    public array $activeAreas=[7,8];
    public bool $hasActiveArea=true;

    public function findActiveModule(string$moduleKey):?array{return$this->modules[$moduleKey]??null;}
    public function individualModuleDecision(int$userId,int$moduleId):?string{return$this->decisions[$moduleId]??null;}
    public function inheritsModuleFromActiveArea(int$userId,int$moduleId):bool{return in_array($moduleId,$this->inherited,true);}
    public function activeAreaIdsForUser(int$userId):array{return$this->userAreas;}
    public function activeAreaExists(int$areaId):bool{return in_array($areaId,$this->activeAreas,true);}
    public function visibleActiveModules():array{return array_values($this->modules);}
    public function userHasActiveArea(int$userId):bool{return$this->hasActiveArea;}
    public function userHasActiveModuleDecision(int$userId):bool{return$this->decisions!==[];}
}

$repository=new InMemoryOrganizationalAccessRepository();
$user=new AuthenticatedUser(20,'Persona Sistemas','persona',[],['escaneres.ver']);
$access=new OrganizationalAccess($user,$repository);

test('sin excepción ni herencia no accede',fn()=>ok(!$access->canAccessModule('control_escaneres')));
$repository->inherited=[2];
test('área activa hereda módulo',fn()=>ok($access->canAccessModule('control_escaneres')));
$repository->decisions[2]='denegar';
test('denegación individual prevalece sobre área',fn()=>ok(!$access->canAccessModule('control_escaneres')));
$repository->inherited=[];$repository->decisions[2]='permitir';
test('permiso individual habilita módulo',fn()=>ok($access->canAccessModule('control_escaneres')));
$repository->decisions[2]='denegar';
$global=new OrganizationalAccess(new AuthenticatedUser(1,'Administrador','admin',[],['modulos.acceso_global','areas.acceso_global']),$repository);
test('denegación explícita prevalece sobre acceso global',fn()=>ok(!$global->canAccessModule('control_escaneres')));
unset($repository->decisions[2]);
test('permiso global habilita módulos activos',fn()=>ok($global->canAccessModule('control_escaneres')));
test('módulo inexistente o inactivo se rechaza',fn()=>ok(!$global->canAccessModule('inexistente')));
test('usuario accede únicamente a su área activa',fn()=>ok($access->canAccessArea(7)&&!$access->canAccessArea(8)));
test('área inactiva siempre se rechaza',fn()=>ok(!$global->canAccessArea(999)));
test('alcance global admite cualquier área activa',fn()=>ok($global->canAccessArea(8)));
test('IDs de áreas autorizadas provienen del backend',fn()=>ok($access->authorizedAreaIds()===[7]));
$repository->decisions[2]='permitir';
test('módulo no sustituye permiso de acción',function()use($access){try{$access->requireModuleAccess('control_escaneres','escaneres.editar');}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Aceptó una acción sin permiso.');});
unset($repository->decisions[2]);
$repository->decisions[3]='permitir';
$navigation=(new ModuleNavigationBuilder($access))->build('/Ferrocheck/public');
test('sidebar contiene sólo módulos autorizados',fn()=>ok(count($navigation)===1&&$navigation[0]['key']==='administracion'));
test('sidebar conserva ruta controlada y orden de catálogo',fn()=>ok($navigation[0]['url']==='/Ferrocheck/public/index.php?modulo=administracion'));
$legacyRepository=new InMemoryOrganizationalAccessRepository();$legacyRepository->hasActiveArea=false;$legacyAccess=new OrganizationalAccess($user,$legacyRepository);
test('usuario sin área no recibe módulos tras cerrar el backfill',fn()=>ok(!$legacyAccess->canAccessModule('dashboard')));

$migration=file_get_contents(dirname(__DIR__,2).'/database/migrations/20260722_013_prepare_organizational_access.sql');
$rollback=file_get_contents(dirname(__DIR__,2).'/database/migrations/20260722_013_prepare_organizational_access.rollback.sql');
test('migración declara las cinco tablas de acceso',fn()=>ok(count(array_filter(['areas_organizacionales','usuario_areas','modulos','area_modulos','usuario_modulos'],fn(string$t):bool=>str_contains($migration,'CREATE TABLE '.$t)))===5));
test('migración inserta únicamente áreas aprobadas',fn()=>ok(str_contains($migration,"'sistemas'")&&str_contains($migration,"'calidad'")&&str_contains($migration,"'embarques'")&&str_contains($migration,"'administracion'")&&!preg_match("/'seguridad'|'recibo'|'logistica'|'inventarios'|'recursos_humanos'/i",$migration)));
test('migración separa propiedad organizacional del área operativa',fn()=>ok(str_contains($migration,'area_organizacional_id')&&str_contains($migration,'REFERENCES areas_organizacionales')));
test('rollback retira dependencias en orden seguro',fn()=>ok(strpos($rollback,'DROP COLUMN area_organizacional_id')<strpos($rollback,'DROP TABLE IF EXISTS areas_organizacionales')));
test('permisos organizacionales y globales están preparados',fn()=>ok(str_contains($migration,"'areas.acceso_global'")&&str_contains($migration,"'modulos.acceso_global'")&&str_contains($migration,"'modulos.asignar'")));

finish('Organizational Access');
