<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este comando sólo puede ejecutarse desde CLI.\n");
    exit(1);
}

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;

$options = getopt('', ['nombre:','empleado:','usuario:','correo::','password-stdin']);
$ask = static function (string $key, string $label) use ($options): string {
    $value = isset($options[$key]) ? (string)$options[$key] : '';
    if ($value === '') {
        fwrite(STDOUT, $label . ': ');
        $value = trim((string)fgets(STDIN));
    }
    return trim($value);
};

$name = $ask('nombre', 'Nombre completo');
$employee = strtoupper($ask('empleado', 'Número de empleado'));
$username = mb_strtolower($ask('usuario', 'Usuario'));
$email = mb_strtolower($ask('correo', 'Correo (opcional)'));
if (!array_key_exists('password-stdin', $options)) {
    fwrite(STDERR, "La contraseña debe enviarse por entrada estándar usando --password-stdin.\n");
    exit(1);
}
$password = rtrim((string)fgets(STDIN), "\r\n");

if ($name === '' || mb_strlen($name) > 150 || $employee === '' || mb_strlen($employee) > 40 || !preg_match('/^[a-z0-9._-]{3,80}$/', $username)) {
    fwrite(STDERR, "Los datos del administrador no son válidos.\n"); exit(1);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "El correo no es válido.\n"); exit(1);
}
if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "La contraseña no cumple la fortaleza mínima.\n"); exit(1);
}

$pdo = Database::getConnection();
$pdo->beginTransaction();
try {
    $duplicate = $pdo->prepare('SELECT 1 FROM usuarios WHERE usuario=:usuario OR numero_empleado=:empleado OR (:correo IS NOT NULL AND correo=:correo2) LIMIT 1');
    $mail = $email === '' ? null : $email;
    $duplicate->execute(['usuario'=>$username,'empleado'=>$employee,'correo'=>$mail,'correo2'=>$mail]);
    if ($duplicate->fetchColumn() !== false) throw new DomainException('Ya existe un usuario con esos datos.');
    $roleId = (int)$pdo->query("SELECT id FROM roles WHERE nombre='Administrador' AND activo=1 LIMIT 1")->fetchColumn();
    if ($roleId < 1) throw new RuntimeException('La migración de autenticación no está aplicada correctamente.');
    $insert = $pdo->prepare('INSERT INTO usuarios(nombre,numero_empleado,usuario,correo,password_hash,activo)VALUES(:nombre,:empleado,:usuario,:correo,:hash,1)');
    $insert->execute(['nombre'=>$name,'empleado'=>$employee,'usuario'=>$username,'correo'=>$mail,'hash'=>password_hash($password,PASSWORD_DEFAULT)]);
    $userId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO usuario_roles(usuario_id,rol_id,asignado_por)VALUES(:usuario,:rol,NULL)')->execute(['usuario'=>$userId,'rol'=>$roleId]);
    $pdo->prepare("INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_nuevo_json,request_id)VALUES(NULL,'usuario.primer_administrador','autenticacion','usuario',:id,'exito',:data,:request)")->execute(['id'=>$userId,'data'=>json_encode(['rol'=>'Administrador'],JSON_THROW_ON_ERROR),'request'=>bin2hex(random_bytes(16))]);
    $pdo->commit();
    fwrite(STDOUT, "Administrador creado correctamente.\n");
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "No fue posible crear el administrador: " . $error->getMessage() . "\n");
    exit(1);
} finally {
    $password = str_repeat("\0", strlen($password));
    unset($password);
}
