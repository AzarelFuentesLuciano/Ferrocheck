<?php
declare(strict_types=1);
require dirname(__DIR__) . '/control-escaneres/bootstrap.php';

use App\Auth\{AuthenticatedUser,Authorization,Csrf,ForbiddenException,SessionSecurity};
use App\Repositories\AuthRepository;
use App\Services\AuthService;

$session=[];$csrf=new Csrf($session);$token=$csrf->token();
test('CSRF general genera y valida token de 64 caracteres',fn()=>ok(strlen($token)===64&&$csrf->validate($token)&&!$csrf->validate('incorrecto')));
test('rotar CSRF invalida el token anterior',function()use($csrf,$token){$next=$csrf->rotate();ok($next!==$token&&!$csrf->validate($token)&&$csrf->validate($next));});
$normal=new AuthenticatedUser(2,'Operador','operador',['Operador'],['escaneres.ver']);
test('permiso explícito autoriza',function()use($normal){(new Authorization($normal))->require('escaneres.ver');ok(true);});
test('usuario normal recibe prohibición administrativa',function()use($normal){try{(new Authorization($normal))->require('usuarios.crear');}catch(ForbiddenException){ok(true);return;}throw new RuntimeException('No se produjo 403 lógico.');});
test('sesión almacena huella irreversible',fn()=>ok(SessionSecurity::fingerprint('session-id')===hash('sha256','session-id')));
$authSession=[];$authService=new AuthService(new AuthRepository(new PDO('sqlite::memory:')),$authSession);
test('retorno rechaza esquemas y URL protocol-relative',fn()=>ok(!str_contains($authService->safeReturn('javascript:alert(1)'),'javascript:')&&!str_contains($authService->safeReturn('//evil.example/path'),'evil.example')&&!str_contains($authService->safeReturn('https://evil.example/path'),'evil.example')));
$migration=file_get_contents(dirname(__DIR__,2).'/database/migrations/20260721_012_create_authentication.sql');
test('migración no contiene usuarios ni contraseñas predeterminadas',fn()=>ok(!preg_match('/INSERT\s+INTO\s+usuarios/i',$migration)&&!str_contains($migration,'admin/')));
test('migración declara seis tablas y permisos únicos',fn()=>ok(substr_count($migration,'CREATE TABLE ')===6&&str_contains($migration,'uq_permisos_clave')));
$login=file_get_contents(dirname(__DIR__,2).'/app/Views/auth/login.php');
test('login no ofrece registro público',fn()=>ok(!preg_match('/Registrarse|Crear cuenta|Sign up/i',$login)));
$api=file_get_contents(dirname(__DIR__,2).'/public/control-escaneres-api.php');
test('API antigua queda explícitamente deshabilitada',fn()=>ok(str_contains($api,'http_response_code(410)')));
finish('Authentication Unit');
