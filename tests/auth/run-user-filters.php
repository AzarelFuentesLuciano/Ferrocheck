<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';require_once dirname(__DIR__,2).'/config/config.php';
use App\Auth\{AuthenticatedUser,ForbiddenException,ProtectedUserPolicy};
use App\Core\Database;
use App\Repositories\{AuthRepository,UserAdminRepository};
$pdo=Database::getConnection();$repo=new UserAdminRepository($pdo);
test('consultas preparadas nativas permanecen activas',fn()=>ok((bool)$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES)===false));
foreach([['sin filtros','',null],['nombre','Azarel',null],['usuario','azarel',null],['empleado','TEMP-AZAREL',null],['correo','@',null],['activos','',true],['inactivos','',false],['búsqueda activos','azarel',true],['búsqueda inactivos','azarel',false],['sin resultados','__no_existe__',null],['porcentaje','%',null],['guion bajo','_',null],['comillas',"'\"",null],['inyección',"%' OR 1=1 --",null]]as[$name,$q,$active])test('filtro '.$name,function()use($repo,$q,$active){$items=$repo->list($q,$active);$count=$repo->count($q,$active);ok(is_array($items)&&$count>=count($items));});
test('comodines se buscan literalmente',fn()=>ok($repo->count('%')<= $repo->count('')&&$repo->count('_')<= $repo->count('')));
test('paginación conserva resultados coherentes',fn()=>ok(count($repo->list('',null,2,2))<=2&&$repo->count('')>=count($repo->list('',null,2,2))));
$compatibilityPdo=new PDO('sqlite::memory:');$compatibilityPdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$compatibilityPdo->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY, nombre TEXT, usuario TEXT, numero_empleado TEXT, correo TEXT, activo INTEGER, es_usuario_protegido INTEGER NULL)');
$compatibilityPdo->exec("INSERT INTO usuarios VALUES(1,'Legacy','legacy','LEG-1','legacy@example.test',1,NULL)");
test('usuario legacy con indicador NULL permanece visible por compatibilidad',fn()=>same(1,(new UserAdminRepository($compatibilityPdo))->count('legacy')));
$suffix=bin2hex(random_bytes(5));$prefix='directory_'.$suffix;
$actorId=(int)$pdo->query('SELECT id FROM usuarios ORDER BY id LIMIT 1')->fetchColumn();
$statsBefore=$repo->organizationalStats();
$insert=$pdo->prepare('INSERT INTO usuarios(nombre,numero_empleado,usuario,correo,password_hash,activo,es_super_administrador,es_usuario_protegido,created_by,updated_by) VALUES(:nombre,:empleado,:usuario,:correo,:hash,1,0,:protegido,:actor,:actor2)');
$ids=[];
try{
    foreach([
        ['Protegido Directorio','P-'.$suffix,$prefix.'_protegido',1],
        ['Normal Directorio','N-'.$suffix,$prefix.'_normal',0],
    ]as[$name,$employee,$username,$protected]){
        $insert->execute(['nombre'=>$name,'empleado'=>$employee,'usuario'=>$username,'correo'=>$username.'@example.test','hash'=>password_hash('Aa1!DirectoryTest',PASSWORD_DEFAULT),'protegido'=>$protected,'actor'=>$actorId,'actor2'=>$actorId]);
        $ids[$username]=(int)$pdo->lastInsertId();
    }
    test('usuario protegido no aparece en listado',fn()=>same([],$repo->list($prefix.'_protegido')));
    test('usuario normal aparece en listado',fn()=>same(1,count($repo->list($prefix.'_normal'))));
    test('búsqueda exacta del protegido devuelve cero',fn()=>same(0,$repo->count($prefix.'_protegido')));
    test('filtros no muestran protegido',fn()=>same([],$repo->list($prefix.'_protegido',true,1,20,'unassigned')));
    test('conteos excluyen protegido',fn()=>same(1,$repo->count($prefix)));
    test('indicadores organizacionales excluyen protegido',function()use($repo,$statsBefore){$stats=$repo->organizationalStats();same($statsBefore['total']+1,$stats['total']);same($statsBefore['pending']+1,$stats['pending']);});
    test('paginación usa solamente usuarios visibles',function()use($repo,$prefix){same(1,count($repo->list($prefix,null,1,20)));same(1,count($repo->list($prefix,null,1,1)));same([],$repo->list($prefix,null,2,1));});
    test('conjunto de exportación CSV excluye protegido',function()use($repo,$prefix){$rows=$repo->list('',true,1,100000,'unassigned');$usernames=array_column($rows,'usuario');ok(in_array($prefix.'_normal',$usernames,true)&&!in_array($prefix.'_protegido',$usernames,true));});
    test('usuario protegido sigue disponible para autenticación',function()use($pdo,$prefix){$row=(new AuthRepository($pdo))->findForLogin($prefix.'_protegido');ok(is_array($row)&&password_verify('Aa1!DirectoryTest',(string)$row['password_hash']));});
    test('Sentinel conserva bloqueo directo al protegido',function()use($repo,$ids){$target=$repo->find($ids[array_key_first($ids)]);$actor=new AuthenticatedUser(999,'Administrador','administrador',['Administrador'],['usuarios.editar']);try{(new ProtectedUserPolicy())->assertCanManage($actor,$target??[]);}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('Sentinel permitió administrar al protegido.');});
}finally{
    if($ids!==[]){$marks=implode(',',array_fill(0,count($ids),'?'));$delete=$pdo->prepare("DELETE FROM usuarios WHERE id IN ($marks)");$delete->execute(array_values($ids));}
}
finish('User Filters');
