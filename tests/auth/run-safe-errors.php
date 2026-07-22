<?php
declare(strict_types=1);
require dirname(__DIR__).'/control-escaneres/bootstrap.php';
$handler=file_get_contents(dirname(__DIR__,2).'/app/Support/GlobalErrorHandler.php');$view=file_get_contents(dirname(__DIR__,2).'/app/Views/errors/500.php');
test('oculta errores PHP en navegador',fn()=>ok(str_contains($handler,"display_errors','0")&&str_contains($handler,"display_startup_errors','0")));
test('registra detalle técnico y seguimiento',fn()=>ok(str_contains($handler,'getTraceAsString')&&str_contains($handler,'trackingId')&&str_contains($handler,'REQUEST_METHOD')&&str_contains($handler,'REQUEST_URI')));
test('pantalla segura no expone detalle interno',fn()=>ok(str_contains($view,'No fue posible cargar la información')&&str_contains($view,'Seguimiento:')&&!str_contains($view,'getMessage')&&!str_contains($view,'getTrace')));
test('respuesta usa código 500 y no-store',fn()=>ok(str_contains($handler,'http_response_code(500)')&&str_contains($handler,'Cache-Control: no-store')));
finish('Safe Errors');
