<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Validators\ControlEscaneres\ScannerRegistrationValidator;
$validator=new ScannerRegistrationValidator();
foreach([['556321456987452',true],['12345678901234',false],['1234567890123456',false],['12345678901234A',false],['12345678901234-',false],[' 556321456987452 ',true],['556 321456987452',false],['556-321456987452',false],['',true],['012345678901234',true]]as[$value,$expected])test('IMEI '.var_export($value,true),fn()=>same($validator->isValidImei($value),$expected));
test('conserva cero inicial',fn()=>same($validator->normalizeImei(' 012345678901234 '),'012345678901234'));
test('valor exacto tiene 15 dígitos',fn()=>ok(strlen('556321456987452')===15&&ctype_digit('556321456987452')));
finish('IMEI Validation');
