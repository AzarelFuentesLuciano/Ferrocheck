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
test('retorno permite únicamente index interno',fn()=>ok($authService->safeReturn('/index.php?modulo=control-escaneres')==='/index.php?modulo=control-escaneres'&&$authService->safeReturn('/Ferrocheck/public/index.php?modulo=dashboard')==='/Ferrocheck/public/index.php?modulo=dashboard'&&!str_contains($authService->safeReturn('/admin/index.php'),'admin/index.php')));
test('retorno rechaza CRLF',fn()=>ok(!str_contains($authService->safeReturn("/index.php\r\nLocation:https://evil.example"),'evil.example')));
$migration=file_get_contents(dirname(__DIR__,2).'/database/migrations/20260721_012_create_authentication.sql');
test('migración no contiene usuarios ni contraseñas predeterminadas',fn()=>ok(!preg_match('/INSERT\s+INTO\s+usuarios/i',$migration)&&!str_contains($migration,'admin/')));
test('migración declara seis tablas y permisos únicos',fn()=>ok(substr_count($migration,'CREATE TABLE ')===6&&str_contains($migration,'uq_permisos_clave')));
$login=file_get_contents(dirname(__DIR__,2).'/app/Views/auth/login.php');
test('login no ofrece registro público',fn()=>ok(!preg_match('/Registrarse|Crear cuenta|Sign up/i',$login)));
test('login contiene footer exclusivo y enlace seguro',fn()=>ok(str_contains($login,'auth-footer-section')&&str_contains($login,'VASCOR OPS V1.0')&&str_contains($login,'target="_blank"')&&str_contains($login,'rel="noopener noreferrer"')));
$authCss=file_get_contents(dirname(__DIR__,2).'/public/assets/css/auth.css');
test('footer queda después del primer viewport sin fixed ni sticky',fn()=>ok(str_contains($authCss,'.auth-viewport')&&str_contains($authCss,'min-height:100svh')&&!preg_match('/auth-footer[^}]*position:(fixed|sticky)/',$authCss)));
$front=file_get_contents(dirname(__DIR__,2).'/public/index.php');
test('front controller aplica guard global antes del dashboard',fn()=>ok(strpos($front,'if ($currentUser === null)')<strpos($front,'new DashboardController()')&&str_contains($front,'Cache-Control: no-store')));
$api=file_get_contents(dirname(__DIR__,2).'/public/control-escaneres-api.php');
test('API antigua queda explícitamente deshabilitada',fn()=>ok(str_contains($api,'http_response_code(410)')));
finish('Authentication Unit');
