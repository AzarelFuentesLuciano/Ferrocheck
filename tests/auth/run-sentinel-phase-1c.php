<?php
declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\{AuthenticatedUser,Authorization};

if(!defined('BASE_URL'))define('BASE_URL','/vascor-test');
$_SESSION['auth_module_keys']=['administracion'];

function sentinelUserRow(int$id,string$name,string$username,bool$protected,bool$super):array
{
    return[
        'id'=>$id,'nombre'=>$name,'numero_empleado'=>'EMP-'.$id,'usuario'=>$username,'correo'=>$username.'@example.test',
        'activo'=>1,'es_super_administrador'=>(int)$super,'es_usuario_protegido'=>(int)$protected,'ultimo_acceso'=>null,'created_at'=>'2026-01-01 00:00:00',
        'roles'=>'Administrador','area_principal'=>'Sistemas','modulos_compatibilidad'=>null,'tiene_historial'=>1,'sesiones_activas'=>0,
        'role_ids'=>[1],'area_ids'=>[1],'principal_area_id'=>1,'module_allow_ids'=>[1],'module_deny_ids'=>[],
    ];
}

function renderSentinelUsers(AuthenticatedUser$actor,array$overrides=[]):string
{
    $context=new class(new Authorization($actor)){
        public function __construct(public Authorization$authorization){}
    };
    $normal=sentinelUserRow(20,'Usuario Normal Fixture','normal_fixture',false,false);
    $defaults=[
        'action'=>'listar','user'=>null,'items'=>[$normal],'total'=>1,'organizationalStats'=>['total'=>1,'assigned'=>1,'pending'=>0,'percentage'=>100.0],
        'roles'=>[['id'=>1,'nombre'=>'Administrador','descripcion'=>'','activo'=>1,'es_sistema'=>1]],
        'areas'=>[['id'=>1,'clave'=>'sistemas','nombre'=>'Sistemas','descripcion'=>'','activo'=>1]],
        'modules'=>[['id'=>1,'clave'=>'administracion','nombre'=>'Administración','descripcion'=>'','ruta'=>'administracion','icono'=>'','orden'=>1,'activo'=>1,'visible_menu'=>1]],
        'search'=>'','active'=>null,'areaFilter'=>'','page'=>1,'areaPreview'=>null,'csrfToken'=>'sentinel-csrf','message'=>null,
    ];
    $render=function(array$variables):string{
        extract($variables,EXTR_SKIP);
        ob_start();
        require dirname(__DIR__,2).'/app/Views/admin/users.php';
        return(string)ob_get_clean();
    };
    return$render->call($context,array_replace($defaults,$overrides));
}

$permissions=['administracion.acceder','usuarios.ver','usuarios.crear','usuarios.editar','usuarios.desactivar','usuarios.restablecer_password','areas.asignar'];
$normalActor=new AuthenticatedUser(1,'Administrador Normal','admin_normal',['Administrador'],$permissions);
$superActor=new AuthenticatedUser(2,'Actor Sentinel','actor_sentinel',['Administrador'],$permissions,true,true);
$normalRow=sentinelUserRow(20,'Usuario Normal Fixture','normal_fixture',false,false);
$protectedRow=sentinelUserRow(21,'Usuario Protegido Fixture','protegido_fixture',true,true);
$visibleSuperRow=sentinelUserRow(22,'Super Administrador Visible','super_visible',false,true);

$superList=renderSentinelUsers($superActor,['items'=>[$normalRow,$visibleSuperRow],'total'=>2,'organizationalStats'=>['total'=>2,'assigned'=>2,'pending'=>0,'percentage'=>100.0]]);
$normalList=renderSentinelUsers($normalActor,['items'=>[$normalRow]]);
$protectedEdit=renderSentinelUsers($superActor,['action'=>'editar','user'=>$protectedRow,'items'=>[$protectedRow]]);
$normalEdit=renderSentinelUsers($superActor,['action'=>'editar','user'=>$normalRow,'items'=>[$normalRow]]);
$passwordView=renderSentinelUsers($superActor,['action'=>'password','user'=>$normalRow,'items'=>[$normalRow]]);

test('Directorio de Super Administrador no renderiza usuario protegido',fn()=>ok(!str_contains($superList,'Usuario Protegido Fixture')&&!str_contains($superList,'protegido_fixture')&&!str_contains($superList,'badge--sentinel-protected')));
test('Super Administrador ve insignia Super Administrador',fn()=>ok(str_contains($superList,'badge--sentinel-super')&&str_contains($superList,'>Super Administrador</span>')));
test('Administrador normal no recibe identidad ni indicadores protegidos',fn()=>ok(!str_contains($normalList,'Usuario Protegido Fixture')&&!str_contains($normalList,'protegido_fixture')&&!str_contains($normalList,'badge--sentinel-protected')&&!str_contains($normalList,'>Protegido</span>')));
test('formulario protegido muestra aviso Sentinel',fn()=>ok(str_contains($protectedEdit,'Este usuario está protegido por Sentinel.')));
test('formulario normal no muestra aviso Sentinel',fn()=>ok(!str_contains($normalEdit,'Este usuario está protegido por Sentinel.')));
test('formularios no envían indicadores Sentinel',function()use($protectedEdit,$normalEdit){foreach([$protectedEdit,$normalEdit]as$html)ok(!preg_match('/\bname=["\'](?:es_usuario_protegido|es_super_administrador)["\']/i',$html));});
test('acciones administrativas existentes permanecen disponibles',function()use($superList,$protectedEdit,$passwordView){ok(str_contains($superList,'accion=editar')&&str_contains($superList,'accion=asignar-area')&&str_contains($protectedEdit,'name="role_ids[]"')&&str_contains($protectedEdit,'name="principal_area_id"')&&str_contains($protectedEdit,'module_decision')&&str_contains($protectedEdit,'name="activo"')&&str_contains($passwordView,'name="operation" value="password"'));});
test('encabezado Super Administrador conserva rol real',fn()=>ok(str_contains($superList,'<small>Administrador</small>')));
test('insignia Super Administrador aparece separada del rol',fn()=>ok(str_contains($superList,'class="app-header-user__sentinel-badge">Super Administrador</span>')&&!str_contains($superList,'Administrador · Super Administrador')));
test('encabezado normal conserva rol sin insignia Sentinel',fn()=>ok(str_contains($normalList,'<small>Administrador</small>')&&!str_contains($normalList,'app-header-user__sentinel-badge')&&!str_contains($normalList,'Administrador · Super Administrador')));
test('encabezado conserva versión, fecha, hora, avatar y cierre de sesión',fn()=>ok(str_contains($superList,'app-header-meta__version')&&str_contains($superList,'data-app-shell-date')&&str_contains($superList,'data-app-shell-time')&&str_contains($superList,'app-header-user__avatar')&&str_contains($superList,'app-header-logout')));
test('renderizado conserva estructura responsive de tabla y acciones',fn()=>ok(str_contains($superList,'class="table-responsive"')&&str_contains($superList,'class="actions"')));

finish('Sentinel Phase 1C');
