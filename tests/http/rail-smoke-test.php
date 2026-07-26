<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$structuralOnly = in_array('--structural-only', $argv, true);
$passed = 0;
$failed = 0;
$report = static function (string $name, bool $result) use (&$passed, &$failed): void {
    $result ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $result ? 'PASS' : 'FAIL', $name);
};

$publicSource = (string) file_get_contents($root . '/public/index.php');
$migration = (string) file_get_contents($root . '/database/migrations/20260725_015_register_rail_module.sql');
$rollback = (string) file_get_contents($root . '/database/migrations/20260725_015_register_rail_module.rollback.sql');
$report('ruta pública Rail está detrás del guard global', strpos($publicSource, 'if ($currentUser === null)') < strpos($publicSource, "if ((\$_GET['modulo'] ?? '') === 'rail')"));
$report('ruta pública exige acceso organizacional y rail.ver', str_contains($publicSource, "requireModuleAccess('rail', 'rail.ver')"));
$report('migración registra Rail activo y visible con orden 15', str_contains($migration, "VALUES ('rail','Rail','Operación ferroviaria modular','rail','▰',15,1,1)"));
$report('migración limita área a Sistemas y rol a Administrador', str_contains($migration, "a.clave='sistemas'") && str_contains($migration, "r.nombre='Administrador'") && !preg_match("/r\\.nombre='(?:Supervisor|Operador|Consulta)'/", $migration));
$report('rollback protege relaciones restantes', substr_count($rollback, 'NOT EXISTS') >= 3 && str_contains($rollback, 'usuario_modulos'));

if (!$structuralOnly) {
    $baseUrl = rtrim((string) getenv('RAIL_SMOKE_BASE_URL'), '/');
    $username = (string) getenv('RAIL_SMOKE_AUTH_USER');
    $password = (string) getenv('RAIL_SMOKE_AUTH_PASSWORD');
    if ($baseUrl === '' || $username === '' || $password === '') {
        fwrite(STDERR, "RAIL_SMOKE_BASE_URL, RAIL_SMOKE_AUTH_USER y RAIL_SMOKE_AUTH_PASSWORD son obligatorios.\n");
        exit(2);
    }
    $entryUrl = str_ends_with($baseUrl, '/index.php') ? $baseUrl : $baseUrl . '/index.php';
    $cookieFile = tempnam(sys_get_temp_dir(), 'rail-smoke-');
    if ($cookieFile === false) {
        throw new RuntimeException('No fue posible crear el almacén temporal de cookies.');
    }
    $request = static function (string $url, string $method = 'GET', array $data = []) use ($cookieFile): array {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_POST => $method === 'POST',
            CURLOPT_POSTFIELDS => $method === 'POST' ? http_build_query($data) : null,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        return ['status'=>$status,'body'=>is_string($body)?$body:'','error'=>$error];
    };
    try {
        $returnUrl = '/index.php?modulo=rail';
        $login = $request($entryUrl . '?modulo=auth&return=' . rawurlencode($returnUrl));
        preg_match('/name="_csrf" value="([^"]+)"/', $login['body'], $csrfMatch);
        $csrf = html_entity_decode($csrfMatch[1] ?? '', ENT_QUOTES, 'UTF-8');
        $report('login Rail entrega CSRF', $login['status'] === 200 && $csrf !== '');
        $authenticated = $request($entryUrl . '?modulo=auth', 'POST', [
            '_csrf'=>$csrf,
            'return'=>$returnUrl,
            'usuario'=>$username,
            'password'=>$password,
        ]);
        $report('Rail autenticado responde App Shell', $authenticated['status'] === 200 && str_contains($authenticated['body'], 'class="rail-module"') && substr_count($authenticated['body'], '<html') === 1);
        $report('Rail HTTP carga CSS y navegación interna', str_contains($authenticated['body'], '/assets/css/rail/rail.css') && substr_count($authenticated['body'], '<nav class="rail-') === 2);
    } finally {
        unlink($cookieFile);
    }
}

echo "\nResumen Rail HTTP Smoke: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
