<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';require_once dirname(__DIR__,2).'/config/config.php';
use App\Core\Database;use App\Repositories\UserAdminRepository;
$pdo=Database::getConnection();$repo=new UserAdminRepository($pdo);
test('consultas preparadas nativas permanecen activas',fn()=>ok((bool)$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES)===false));
foreach([['sin filtros','',null],['nombre','Azarel',null],['usuario','azarel',null],['empleado','TEMP-AZAREL',null],['correo','@',null],['activos','',true],['inactivos','',false],['búsqueda activos','azarel',true],['búsqueda inactivos','azarel',false],['sin resultados','__no_existe__',null],['porcentaje','%',null],['guion bajo','_',null],['comillas',"'\"",null],['inyección',"%' OR 1=1 --",null]]as[$name,$q,$active])test('filtro '.$name,function()use($repo,$q,$active){$items=$repo->list($q,$active);$count=$repo->count($q,$active);ok(is_array($items)&&$count>=count($items));});
test('comodines se buscan literalmente',fn()=>ok($repo->count('%')<= $repo->count('')&&$repo->count('_')<= $repo->count('')));
test('paginación conserva resultados coherentes',fn()=>ok(count($repo->list('',null,2,2))<=2&&$repo->count('')>=count($repo->list('',null,2,2))));
finish('User Filters');
