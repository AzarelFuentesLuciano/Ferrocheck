<?php
declare(strict_types=1);

require dirname(__DIR__) . '/control-escaneres/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

use App\Auth\{AuthenticatedUser,Authorization,Csrf,ForbiddenException,ProtectedUserPolicy};
use App\Controllers\AdministrationController;
use App\Core\Database;
use App\Repositories\{AuthRepository,OrganizationalAdminRepository,RoleAdminRepository,UserAdminRepository};
use App\Services\{GeneralAuditService,OrganizationalAdminService,RoleAdminService,UserAdminService};

$pdo=Database::getConnection();
$users=new UserAdminRepository($pdo);
$auth=new AuthRepository($pdo);
$roles=new RoleAdminRepository($pdo);
$organizational=new OrganizationalAdminRepository($pdo);
$audit=new GeneralAuditService($pdo);
$policy=new ProtectedUserPolicy();
$userService=new UserAdminService($users,$auth,$audit,$organizational,$policy);
$organizationalService=new OrganizationalAdminService($organizational,$audit,$users,$policy);
$actorId=(int)$pdo->query("SELECT id FROM usuarios WHERE usuario='azarel'")->fetchColumn();
if($actorId<=0)throw new RuntimeException('No existe un actor administrativo para las pruebas Sentinel.');
$permissions=['administracion.acceder','usuarios.ver','usuarios.editar','usuarios.desactivar','usuarios.restablecer_password','areas.asignar'];
$normalActor=new AuthenticatedUser($actorId,'Administrador normal','admin_normal',[], $permissions);
$superActor=new AuthenticatedUser($actorId,'Super Administrador','sentinel_super',[], $permissions,true,true);
$roleId=(int)$pdo->query("SELECT id FROM roles WHERE activo=1 ORDER BY id LIMIT 1")->fetchColumn();
$areaId=(int)$pdo->query("SELECT id FROM areas_organizacionales WHERE activo=1 ORDER BY id LIMIT 1")->fetchColumn();
$moduleId=(int)$pdo->query("SELECT id FROM modulos WHERE activo=1 ORDER BY id LIMIT 1")->fetchColumn();
if($roleId<=0||$areaId<=0||$moduleId<=0)throw new RuntimeException('Faltan catálogos administrativos para las pruebas Sentinel.');

$suffix=bin2hex(random_bytes(6));
$prefix='sentinel_1b_'.$suffix;
$insert=$pdo->prepare('INSERT INTO usuarios(nombre,numero_empleado,usuario,correo,password_hash,activo,es_super_administrador,es_usuario_protegido,created_by,updated_by) VALUES(:nombre,:empleado,:usuario,:correo,:hash,0,0,:protegido,:actor,:actor2)');
$insert->execute(['nombre'=>'Sentinel Protegido '.$suffix,'empleado'=>'SP-'.$suffix,'usuario'=>$prefix.'_protegido','correo'=>$prefix.'_protegido@example.test','hash'=>password_hash('Aa1!PasswordInicial',PASSWORD_DEFAULT),'protegido'=>1,'actor'=>$actorId,'actor2'=>$actorId]);
$protectedId=(int)$pdo->lastInsertId();
$insert->execute(['nombre'=>'Sentinel Normal '.$suffix,'empleado'=>'SN-'.$suffix,'usuario'=>$prefix.'_normal','correo'=>$prefix.'_normal@example.test','hash'=>password_hash('Aa1!PasswordInicial',PASSWORD_DEFAULT),'protegido'=>0,'actor'=>$actorId,'actor2'=>$actorId]);
$normalId=(int)$pdo->lastInsertId();
$assignRole=$pdo->prepare('INSERT INTO usuario_roles(usuario_id,rol_id,asignado_por) VALUES(:usuario,:rol,:actor)');
$assignRole->execute(['usuario'=>$protectedId,'rol'=>$roleId,'actor'=>$actorId]);
$assignRole->execute(['usuario'=>$normalId,'rol'=>$roleId,'actor'=>$actorId]);

$input=static fn(string$name,array$extra=[]):array=>array_replace([
    'nombre'=>$name,
    'numero_empleado'=>'SP-'.$suffix,
    'correo'=>$prefix.'_protegido@example.test',
    'activo'=>'0',
    'role_ids'=>[$roleId],
    'principal_area_id'=>0,
    'area_ids'=>[],
    'module_allow_ids'=>[],
    'module_deny_ids'=>[],
],$extra);

try {
    test('Directorio oculta usuario protegido también a Super Administrador',fn()=>same([],$users->list($prefix.'_protegido')));
    test('Administrador normal no ve usuario protegido',fn()=>same([],$users->list($prefix.'_protegido')));
    test('conteo normal excluye protegido',fn()=>same(0,$users->count($prefix.'_protegido')));
    test('conteo del Directorio excluye protegido sin excepciones',fn()=>same(0,$users->count($prefix.'_protegido')));
    test('paginación permanece consistente al excluir protegidos',function()use($users,$prefix){same(1,$users->count($prefix));same(1,count($users->list($prefix,null,1,1)));same([],$users->list($prefix,null,2,1));});
    test('búsqueda exacta no revela protegido',fn()=>same([],$users->list($prefix.'_protegido')));
    test('objetivo administrativo siempre incluye protección',function()use($users,$protectedId){$target=$users->find($protectedId);ok(is_array($target)&&array_key_exists('es_usuario_protegido',$target));});
    test('política falla cerrada con objetivo incompleto',function()use($policy,$normalActor){try{$policy->assertCanManage($normalActor,['id'=>1]);}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Objetivo incompleto autorizado.');});

    test('update exige AuthenticatedUser y no modifica datos ni relaciones',function()use($userService,$users,$protectedId,$actorId,$input){
        $before=$users->find($protectedId);
        try{$userService->update($protectedId,$input('Cambio sin actor',['role_ids'=>[],'area_ids'=>[999999],'module_allow_ids'=>[999999]]),$actorId);}catch(LogicException$e){same('AuthenticatedUser es obligatorio para operaciones Sentinel.',$e->getMessage());same($before,$users->find($protectedId));return;}
        throw new RuntimeException('update aceptó actor ausente.');
    });
    test('setActive exige AuthenticatedUser y no modifica estado',function()use($userService,$protectedId,$actorId,$pdo){
        $before=(int)$pdo->query("SELECT activo FROM usuarios WHERE id=$protectedId")->fetchColumn();
        try{$userService->setActive($protectedId,true,$actorId);}catch(LogicException$e){same('AuthenticatedUser es obligatorio para operaciones Sentinel.',$e->getMessage());same($before,(int)$pdo->query("SELECT activo FROM usuarios WHERE id=$protectedId")->fetchColumn());return;}
        throw new RuntimeException('setActive aceptó actor ausente.');
    });
    test('resetPassword exige AuthenticatedUser y no modifica hash',function()use($userService,$protectedId,$actorId,$pdo){
        $before=(string)$pdo->query("SELECT password_hash FROM usuarios WHERE id=$protectedId")->fetchColumn();
        try{$userService->resetPassword($protectedId,'Bb2!PasswordNueva','Bb2!PasswordNueva',$actorId);}catch(LogicException$e){same('AuthenticatedUser es obligatorio para operaciones Sentinel.',$e->getMessage());same($before,(string)$pdo->query("SELECT password_hash FROM usuarios WHERE id=$protectedId")->fetchColumn());return;}
        throw new RuntimeException('resetPassword aceptó actor ausente.');
    });
    test('assignUserAreas exige AuthenticatedUser y no modifica áreas',function()use($organizationalService,$protectedId,$areaId,$actorId,$pdo){
        $before=$pdo->query("SELECT area_id,es_principal,activo FROM usuario_areas WHERE usuario_id=$protectedId ORDER BY area_id")->fetchAll(PDO::FETCH_ASSOC);
        try{$organizationalService->assignUserAreas($protectedId,[$areaId],$areaId,$actorId);}catch(LogicException$e){same('AuthenticatedUser es obligatorio para operaciones Sentinel.',$e->getMessage());same($before,$pdo->query("SELECT area_id,es_principal,activo FROM usuario_areas WHERE usuario_id=$protectedId ORDER BY area_id")->fetchAll(PDO::FETCH_ASSOC));return;}
        throw new RuntimeException('assignUserAreas aceptó actor ausente.');
    });
    test('setUserModuleDecision exige AuthenticatedUser y no modifica módulos',function()use($organizationalService,$protectedId,$moduleId,$actorId,$pdo){
        $before=$pdo->query("SELECT modulo_id,tipo,activo FROM usuario_modulos WHERE usuario_id=$protectedId ORDER BY modulo_id")->fetchAll(PDO::FETCH_ASSOC);
        try{$organizationalService->setUserModuleDecision($protectedId,$moduleId,'permitir',true,$actorId);}catch(LogicException$e){same('AuthenticatedUser es obligatorio para operaciones Sentinel.',$e->getMessage());same($before,$pdo->query("SELECT modulo_id,tipo,activo FROM usuario_modulos WHERE usuario_id=$protectedId ORDER BY modulo_id")->fetchAll(PDO::FETCH_ASSOC));return;}
        throw new RuntimeException('setUserModuleDecision aceptó actor ausente.');
    });

    test('Administrador normal recibe HTTP 403 en edición directa',function()use($users,$roles,$organizational,$userService,$organizationalService,$audit,$policy,$normalActor,$protectedId){
        $session=[];$csrf=new Csrf($session);$controller=new AdministrationController(new Authorization($normalActor),$csrf,$users,$roles,$organizational,$userService,new RoleAdminService($roles,$audit),$organizationalService,$policy,$session);
        set_error_handler(static fn(int$severity,string$message):bool=>$severity===E_WARNING&&str_contains($message,'http_response_code()'));
        ob_start();
        try{$controller->dispatch('GET',['seccion'=>'usuarios','accion'=>'editar','id'=>$protectedId],[]);$response=(string)ob_get_contents();}finally{ob_end_clean();restore_error_handler();}
        ok(str_contains($response,'<title>Acceso denegado</title>')&&str_contains($response,'No tienes permiso para realizar esta acción.'));
    });

    test('Administrador normal no actualiza datos ni roles de protegido',function()use($userService,$protectedId,$actorId,$normalActor,$input,$pdo){$before=$pdo->query("SELECT nombre FROM usuarios WHERE id=$protectedId")->fetchColumn();try{$userService->update($protectedId,$input('Cambio prohibido'),$actorId,$normalActor);}catch(ForbiddenException){}same($before,$pdo->query("SELECT nombre FROM usuarios WHERE id=$protectedId")->fetchColumn());});
    test('valor protegido enviado por POST no altera la política',function()use($userService,$protectedId,$actorId,$normalActor,$input){try{$userService->update($protectedId,$input('Cambio prohibido',['es_usuario_protegido'=>0]),$actorId,$normalActor);}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Bandera POST alteró la política.');});
    test('Administrador normal no cambia áreas de protegido',function()use($organizationalService,$protectedId,$areaId,$actorId,$normalActor,$pdo){try{$organizationalService->assignUserAreas($protectedId,[$areaId],$areaId,$actorId,$normalActor);}catch(ForbiddenException){}same(0,(int)$pdo->query("SELECT COUNT(*) FROM usuario_areas WHERE usuario_id=$protectedId AND activo=1")->fetchColumn());});
    test('Administrador normal no cambia módulos de protegido',function()use($organizationalService,$protectedId,$moduleId,$actorId,$normalActor,$pdo){try{$organizationalService->setUserModuleDecision($protectedId,$moduleId,'permitir',true,$actorId,$normalActor);}catch(ForbiddenException){}same(0,(int)$pdo->query("SELECT COUNT(*) FROM usuario_modulos WHERE usuario_id=$protectedId AND activo=1")->fetchColumn());});
    test('Administrador normal no activa protegido',function()use($userService,$protectedId,$actorId,$normalActor,$pdo){try{$userService->setActive($protectedId,true,$actorId,$normalActor);}catch(ForbiddenException){}same(0,(int)$pdo->query("SELECT activo FROM usuarios WHERE id=$protectedId")->fetchColumn());});
    test('Administrador normal no cambia contraseña de protegido',function()use($userService,$protectedId,$actorId,$normalActor,$pdo){$before=$pdo->query("SELECT password_hash FROM usuarios WHERE id=$protectedId")->fetchColumn();try{$userService->resetPassword($protectedId,'Bb2!PasswordNueva','Bb2!PasswordNueva',$actorId,$normalActor);}catch(ForbiddenException){}same($before,$pdo->query("SELECT password_hash FROM usuarios WHERE id=$protectedId")->fetchColumn());});

    test('Super Administrador actualiza datos, roles y excepciones',function()use($userService,$protectedId,$actorId,$superActor,$input,$moduleId,$pdo){$userService->update($protectedId,$input('Cambio Sentinel',['module_allow_ids'=>[$moduleId]]),$actorId,$superActor);same('Cambio Sentinel',$pdo->query("SELECT nombre FROM usuarios WHERE id=$protectedId")->fetchColumn());same(1,(int)$pdo->query("SELECT COUNT(*) FROM usuario_roles WHERE usuario_id=$protectedId")->fetchColumn());same(1,(int)$pdo->query("SELECT COUNT(*) FROM usuario_modulos WHERE usuario_id=$protectedId AND modulo_id=$moduleId AND tipo='permitir' AND activo=1")->fetchColumn());});
    test('Super Administrador cambia áreas',function()use($organizationalService,$protectedId,$areaId,$actorId,$superActor,$pdo){$organizationalService->assignUserAreas($protectedId,[$areaId],$areaId,$actorId,$superActor);same(1,(int)$pdo->query("SELECT COUNT(*) FROM usuario_areas WHERE usuario_id=$protectedId AND area_id=$areaId AND es_principal=1 AND activo=1")->fetchColumn());});
    test('Super Administrador configura decisión individual de módulo',function()use($organizationalService,$protectedId,$moduleId,$actorId,$superActor,$pdo){$organizationalService->setUserModuleDecision($protectedId,$moduleId,'denegar',true,$actorId,$superActor);same('denegar',$pdo->query("SELECT tipo FROM usuario_modulos WHERE usuario_id=$protectedId AND modulo_id=$moduleId AND activo=1")->fetchColumn());});
    test('Super Administrador activa protegido',function()use($userService,$protectedId,$actorId,$superActor,$pdo){$userService->setActive($protectedId,true,$actorId,$superActor);same(1,(int)$pdo->query("SELECT activo FROM usuarios WHERE id=$protectedId")->fetchColumn());});
    test('Super Administrador cambia contraseña protegida',function()use($userService,$protectedId,$actorId,$superActor,$pdo){$password='Cc3!PasswordSentinel';$userService->resetPassword($protectedId,$password,$password,$actorId,$superActor);ok(password_verify($password,(string)$pdo->query("SELECT password_hash FROM usuarios WHERE id=$protectedId")->fetchColumn()));});

    test('usuario normal no protegido conserva actualización',function()use($userService,$normalId,$actorId,$normalActor,$roleId,$prefix,$suffix,$pdo){$userService->update($normalId,['nombre'=>'Normal actualizado','numero_empleado'=>'SN-'.$suffix,'correo'=>$prefix.'_normal@example.test','activo'=>'0','role_ids'=>[$roleId],'principal_area_id'=>0,'area_ids'=>[],'module_allow_ids'=>[],'module_deny_ids'=>[]],$actorId,$normalActor);same('Normal actualizado',$pdo->query("SELECT nombre FROM usuarios WHERE id=$normalId")->fetchColumn());});

    $sources='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__,2).'/app'))as$file)if($file->isFile()&&$file->getExtension()==='php')$sources.=file_get_contents($file->getPathname());
    test('no existe autorización por azarel ni ID fijo',fn()=>ok(!preg_match('/usuario\s*={1,3}\s*[\'"]azarel[\'"]|username\s*={1,3}\s*[\'"]azarel[\'"]|->id\s*={2,3}\s*1\b/i',$sources)));
} finally {
    foreach(['usuario_sesiones','usuario_modulos','usuario_areas','usuario_roles']as$table)$pdo->exec("DELETE FROM $table WHERE usuario_id IN ($protectedId,$normalId)");
    $pdo->exec("DELETE FROM usuarios WHERE id IN ($protectedId,$normalId)");
}

finish('Sentinel Phase 1B');
