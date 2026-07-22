<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';
require_once dirname(__DIR__,2).'/config/config.php';

use App\Core\Database;
use App\Repositories\{AuthRepository,RoleAdminRepository,UserAdminRepository};
use App\Services\{GeneralAuditService,RoleAdminService,UserAdminService};

$pdo=Database::getConnection();$auth=new AuthRepository($pdo);$users=new UserAdminRepository($pdo);$roles=new RoleAdminRepository($pdo);$audit=new GeneralAuditService($pdo);$userService=new UserAdminService($users,$auth,$audit);$roleService=new RoleAdminService($roles,$audit);
$admin=$pdo->query("SELECT id,nombre,numero_empleado,usuario,correo,activo FROM usuarios WHERE usuario='azarel'")->fetch(PDO::FETCH_ASSOC);if(!is_array($admin))throw new RuntimeException('No existe azarel.');$adminId=(int)$admin['id'];$adminRole=$pdo->query("SELECT id,nombre,descripcion,activo FROM roles WHERE nombre='Administrador'")->fetch(PDO::FETCH_ASSOC);$consultaRole=(int)$pdo->query("SELECT id FROM roles WHERE nombre='Consulta'")->fetchColumn();
$userServiceSource=file_get_contents(dirname(__DIR__,2).'/app/Services/UserAdminService.php');
test('servicio conserva protección del último administrador',fn()=>ok(str_contains($userServiceSource,'No se puede desactivar al último administrador activo.')));
test('servicio conserva protección contra retiro del último rol administrativo',fn()=>ok(str_contains($userServiceSource,'No se puede dejar el sistema sin un administrador activo.')));
test('servicio impide quitar permisos críticos al rol único',function()use($roleService,$adminRole,$adminId){try{$roleService->update((int)$adminRole['id'],['nombre'=>'Administrador','descripcion'=>$adminRole['descripcion'],'activo'=>'1','permission_ids'=>[]],$adminId);}catch(DomainException$e){ok(str_contains($e->getMessage(),'rol administrativo'));return;}throw new RuntimeException('Cambio crítico aceptado.');});
test('ID manipulado no modifica usuarios',function()use($userService,$adminId){try{$userService->setActive(999999999,false,$adminId);}catch(DomainException){ok(true);return;}throw new RuntimeException('ID inexistente aceptado.');});
$hashValid=hash('sha256','fase-c-valid-'.random_bytes(8));$hashRevoked=hash('sha256','fase-c-revoked-'.random_bytes(8));$hashExpired=hash('sha256','fase-c-expired-'.random_bytes(8));
try{$auth->createSession($adminId,$hashValid,'127.0.0.1',hash('sha256','agent-a'),new DateTimeImmutable('+10 minutes'));$auth->createSession($adminId,$hashRevoked,'10.0.0.1',hash('sha256','agent-b'),new DateTimeImmutable('+10 minutes'));$auth->revokeSession($hashRevoked,$adminId,'prueba');$auth->createSession($adminId,$hashExpired,'127.0.0.1',hash('sha256','agent-c'),new DateTimeImmutable('-5 minutes'));test('sesión válida se acepta',fn()=>ok($auth->validSession($adminId,$hashValid)));test('sesión revocada se rechaza',fn()=>ok(!$auth->validSession($adminId,$hashRevoked)));test('sesión expirada se rechaza',fn()=>ok(!$auth->validSession($adminId,$hashExpired)));test('huella incorrecta se rechaza',fn()=>ok(!$auth->validSession($adminId,hash('sha256','incorrecta'))));test('IP y User-Agent son informativos',fn()=>ok($auth->validSession($adminId,$hashValid)));}finally{$s=$pdo->prepare('DELETE FROM usuario_sesiones WHERE session_hash IN (?,?,?)');$s->execute([$hashValid,$hashRevoked,$hashExpired]);}
test('azarel continúa activo y con permisos completos',fn()=>ok((int)$pdo->query("SELECT activo FROM usuarios WHERE id=$adminId")->fetchColumn()===1&&(int)$pdo->query("SELECT COUNT(DISTINCT p.id) FROM usuario_roles ur JOIN rol_permisos rp ON rp.rol_id=ur.rol_id JOIN permisos p ON p.id=rp.permiso_id WHERE ur.usuario_id=$adminId")->fetchColumn()===29));
finish('Authentication Phase C Security');
