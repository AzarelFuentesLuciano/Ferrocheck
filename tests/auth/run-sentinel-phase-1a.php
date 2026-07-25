<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';

use App\Auth\{AuthenticatedUser,AuthenticationRequiredException,Authorization,ForbiddenException,ProtectedUserPolicy};
use App\Repositories\AuthRepository;
use App\Services\AuthService;

function reconstructedSentinelUser(int $databaseSuper,int $databaseProtected,array $sentinelSession):AuthenticatedUser
{
    $pdo=new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY,nombre TEXT,usuario TEXT,password_hash TEXT,activo INTEGER,es_super_administrador INTEGER,es_usuario_protegido INTEGER)');
    $pdo->exec('CREATE TABLE roles(id INTEGER PRIMARY KEY,nombre TEXT,activo INTEGER)');
    $pdo->exec('CREATE TABLE usuario_roles(usuario_id INTEGER,rol_id INTEGER)');
    $pdo->exec('CREATE TABLE permisos(id INTEGER PRIMARY KEY,clave TEXT)');
    $pdo->exec('CREATE TABLE rol_permisos(rol_id INTEGER,permiso_id INTEGER)');
    $pdo->exec('CREATE TABLE usuario_sesiones(usuario_id INTEGER,session_hash TEXT,revocada_at TEXT,expira_at TEXT,ultimo_uso TEXT)');
    $insert=$pdo->prepare("INSERT INTO usuarios VALUES(1,'Sentinel Test','sentinel_test','hash',1,:super,:protected)");
    $insert->execute(['super'=>$databaseSuper,'protected'=>$databaseProtected]);
    $pdo->exec("INSERT INTO roles VALUES(1,'Consulta',1)");
    $pdo->exec('INSERT INTO usuario_roles VALUES(1,1)');
    $pdo->exec("INSERT INTO permisos VALUES(1,'usuarios.ver')");
    $pdo->exec('INSERT INTO rol_permisos VALUES(1,1)');
    $pdo->exec("INSERT INTO usuario_sesiones VALUES(1,'session-sentinel',NULL,'2099-01-01 00:00:00','2026-01-01 00:00:00')");
    $session=['user_id'=>1,'auth_session_hash'=>'session-sentinel']+$sentinelSession;
    $user=(new AuthService(new AuthRepository($pdo),$session))->current();
    if(!$user instanceof AuthenticatedUser)throw new RuntimeException('No se reconstruyó la sesión Sentinel.');
    return$user;
}

$normal=new AuthenticatedUser(20,'Normal','normal',[],[]);
$super=new AuthenticatedUser(21,'Sentinel','sentinel',[],[],true,true);
test('usuario compatible sin Sentinel degrada a false',fn()=>ok(!$normal->isSuperAdministrator()&&!$normal->isProtectedUser()));
test('detecta Super Administrador',fn()=>ok($super->isSuperAdministrator()));
test('detecta usuario protegido',fn()=>ok($super->isProtectedUser()));
test('Authorization sin sesión no es Super Administrador',fn()=>ok(!(new Authorization(null))->isSuperAdministrator()));
test('Authorization detecta Super Administrador',fn()=>ok((new Authorization($super))->isSuperAdministrator()));
test('requireSuperAdministrator exige autenticación',function(){try{(new Authorization(null))->requireSuperAdministrator();}catch(AuthenticationRequiredException){ok(true);return;}throw new RuntimeException('Aceptó sesión ausente.');});
test('requireSuperAdministrator bloquea usuario normal',function()use($normal){try{(new Authorization($normal))->requireSuperAdministrator();}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Aceptó usuario normal.');});
test('requireSuperAdministrator permite nivel Sentinel',function()use($super){(new Authorization($super))->requireSuperAdministrator();ok(true);});

$policy=new ProtectedUserPolicy();
test('política permite objetivo no protegido',fn()=>ok($policy->canView($normal,[])&&$policy->canManage($normal,['es_usuario_protegido'=>0])));
foreach([true,1,'1']as$value)test('política interpreta protegido '.get_debug_type($value),fn()=>ok(!$policy->canView($normal,['es_usuario_protegido'=>$value])));
foreach([false,0,'0']as$value)test('política interpreta no protegido '.get_debug_type($value),fn()=>ok($policy->canView($normal,['es_usuario_protegido'=>$value])));
test('política bloquea protegido para usuario normal',function()use($policy,$normal){try{$policy->assertCanManage($normal,(object)['es_usuario_protegido'=>1]);}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Administración protegida aceptada.');});
test('política permite protegido a Super Administrador',function()use($policy,$super){$policy->assertCanView($super,['es_usuario_protegido'=>'1']);$policy->assertCanManage($super,new AuthenticatedUser(30,'Protegido','protegido',[],[],false,true));ok(true);});

$legacy=new PDO('sqlite::memory:');$legacy->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$legacy->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY,nombre TEXT,usuario TEXT,password_hash TEXT,activo INTEGER)');$legacy->exec("INSERT INTO usuarios VALUES(1,'Legacy','legacy','hash',1)");
$legacyRecord=(new AuthRepository($legacy))->findActiveById(1);
test('AuthRepository tolera esquema previo a migración',fn()=>ok($legacyRecord['es_super_administrador']===0&&$legacyRecord['es_usuario_protegido']===0));
$sentinel=new PDO('sqlite::memory:');$sentinel->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$sentinel->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY,nombre TEXT,usuario TEXT,password_hash TEXT,activo INTEGER,es_super_administrador INTEGER,es_usuario_protegido INTEGER)');$sentinel->exec("INSERT INTO usuarios VALUES(1,'Sentinel','sentinel','hash',1,1,1)");
$sentinelRecord=(new AuthRepository($sentinel))->findForLogin('sentinel');
test('AuthRepository devuelve columnas Sentinel',fn()=>ok((int)$sentinelRecord['es_super_administrador']===1&&(int)$sentinelRecord['es_usuario_protegido']===1));

$root=dirname(__DIR__,2);$authService=file_get_contents($root.'/app/Services/AuthService.php');$repository=file_get_contents($root.'/app/Repositories/AuthRepository.php');$migration=file_get_contents($root.'/database/migrations/20260724_014_create_sentinel_super_admin.sql');$rollback=file_get_contents($root.'/database/migrations/20260724_014_create_sentinel_super_admin.rollback.sql');
test('sesión antigua sin atributos permanece fail-closed',function(){$user=reconstructedSentinelUser(1,1,[]);ok(!$user->isSuperAdministrator()&&!$user->isProtectedUser());});
test('sesión nueva y base autorizada reconstruyen ambos atributos',function(){$user=reconstructedSentinelUser(1,1,['auth_super_administrator'=>true,'auth_protected_user'=>true]);ok($user->isSuperAdministrator()&&$user->isProtectedUser());});
test('revocación de Super Administrador en base prevalece',function(){$user=reconstructedSentinelUser(0,1,['auth_super_administrator'=>true,'auth_protected_user'=>true]);ok(!$user->isSuperAdministrator()&&$user->isProtectedUser());});
test('revocación de usuario protegido en base prevalece',function(){$user=reconstructedSentinelUser(1,0,['auth_super_administrator'=>true,'auth_protected_user'=>true]);ok($user->isSuperAdministrator()&&!$user->isProtectedUser());});
foreach([true,1,'1']as$value)test('AuthService acepta bandera verdadera '.get_debug_type($value),function()use($value){$user=reconstructedSentinelUser(1,1,['auth_super_administrator'=>$value,'auth_protected_user'=>$value]);ok($user->isSuperAdministrator()&&$user->isProtectedUser());});
foreach([false,0,'0','true','01',2,null,'']as$value)test('AuthService rechaza bandera no estricta '.get_debug_type($value).':'.var_export($value,true),function()use($value){$user=reconstructedSentinelUser(1,1,['auth_super_administrator'=>$value,'auth_protected_user'=>$value]);ok(!$user->isSuperAdministrator()&&!$user->isProtectedUser());});
test('SELECT de login y reconstrucción incluye Sentinel',fn()=>ok(str_contains($repository,'es_super_administrador,es_usuario_protegido')&&substr_count($repository,'sentinelProjection()')>=3));
test('migración contiene columnas checks e índices',fn()=>ok(str_contains($migration,'es_super_administrador TINYINT(1) NOT NULL DEFAULT 0')&&str_contains($migration,'es_usuario_protegido TINYINT(1) NOT NULL DEFAULT 0')&&str_contains($migration,'chk_usuarios_super_administrador')&&str_contains($migration,'chk_usuarios_protegido')&&str_contains($migration,'idx_usuarios_activo_super_administrador')));
test('migración inicializa única cuenta autorizada y permisos técnicos',fn()=>ok(substr_count($migration,"usuario='azarel'")===1&&str_contains($migration,'administracion_tecnica.acceder')&&str_contains($migration,'administracion_tecnica.estado.ver')&&str_contains($migration,'administracion_tecnica.auditoria.ver')&&!str_contains($migration,'INSERT INTO rol_permisos')));
test('rollback se limita a artefactos Sentinel y respeta relaciones',fn()=>ok(str_contains($rollback,'DROP COLUMN es_super_administrador')&&str_contains($rollback,'DROP COLUMN es_usuario_protegido')&&str_contains($rollback,'NOT EXISTS')&&str_contains($rollback,'rol_permisos.permiso_id=permisos.id')&&!preg_match('/DROP TABLE|DELETE FROM usuarios|DELETE FROM roles/',$rollback)));
$utf8Files=[$root.'/database/migrations/20260724_014_create_sentinel_super_admin.sql',$root.'/database/migrations/20260724_014_create_sentinel_super_admin.rollback.sql',$root.'/docs/adr/ADR-014-sentinel-super-administrador.md',__FILE__];
test('archivos Fase 1A son UTF-8 real sin mojibake',function()use($utf8Files){foreach($utf8Files as$file){$content=file_get_contents($file);ok(is_string($content)&&mb_check_encoding($content,'UTF-8')&&!str_contains($content,"\xC3\x83")&&!str_contains($content,"\xC3\x82"));}});
$phpSources='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'))as$file)if($file->isFile()&&$file->getExtension()==='php')$phpSources.=file_get_contents($file->getPathname());
test('PHP no autoriza por azarel ni ID fijo',fn()=>ok(!preg_match('/usuario\s*={1,3}\s*[\'"]azarel[\'"]|username\s*={1,3}\s*[\'"]azarel[\'"]|->id\s*={2,3}\s*1\b/i',$phpSources)));
finish('Sentinel Phase 1A');
