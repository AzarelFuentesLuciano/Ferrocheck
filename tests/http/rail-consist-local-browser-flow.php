<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (getenv('CONSIST_LOCAL_BROWSER_RUN') !== '1') {
    fwrite(STDERR, "SKIP: CONSIST_LOCAL_BROWSER_RUN=1 es obligatorio.\n");
    exit(2);
}
$base = rtrim((string) getenv('CONSIST_LOCAL_BASE_URL'), '/');
$dsn = (string) getenv('CONSIST_LOCAL_DSN');
if (!preg_match('#^http://127\.0\.0\.1:\d+/Ferrocheck/public$#', $base)
    || !preg_match('/^mysql:host=(127\.0\.0\.1|localhost);port=\d+;dbname=ferrocheck;/', $dsn)
) {
    throw new RuntimeException('La prueba sólo permite HTTP y base de datos locales.');
}
$pdo = new PDO($dsn, getenv('CONSIST_LOCAL_DB_USER') ?: '', getenv('CONSIST_LOCAL_DB_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$account = $pdo->query(
    "SELECT DISTINCT u.id,u.usuario,u.password_hash FROM usuarios u
     JOIN usuario_roles ur ON ur.usuario_id=u.id JOIN roles r ON r.id=ur.rol_id
     JOIN usuario_areas ua ON ua.usuario_id=u.id AND ua.activo=1
     JOIN area_modulos am ON am.area_id=ua.area_id AND am.activo=1
     JOIN modulos m ON m.id=am.modulo_id AND m.clave='rail' AND m.activo=1
     WHERE u.activo=1 AND r.nombre='Administrador'
     ORDER BY u.id DESC LIMIT 1"
)->fetch();
if (!is_array($account)) {
    throw new RuntimeException('No existe una cuenta administrativa local apta para validación.');
}
$password = bin2hex(random_bytes(24));
$pdo->prepare('UPDATE usuarios SET password_hash=:hash WHERE id=:id')
    ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $account['id']]);

$temp = sys_get_temp_dir() . '/vascor-consist-browser-' . bin2hex(random_bytes(6));
mkdir($temp, 0770, true);
$cookie = $temp . '/cookies.txt';
$inputs = [];
$download = $temp . '/Consist Rail del 29 al 30 de Julio de 2026.xlsx';

$request = static function (
    string $url,
    string $cookie,
    ?array $post = null,
    ?string $output = null,
): array {
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => $output === null,
        CURLOPT_HEADER => $output === null,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_USERAGENT => 'VASCOR-OPS-Local-Validation',
        CURLOPT_TIMEOUT => 120,
    ]);
    if ($post !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post);
    }
    if ($output !== null) {
        $stream = fopen($output, 'wb');
        curl_setopt($handle, CURLOPT_FILE, $stream);
    }
    $raw = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $type = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    $error = curl_error($handle);
    curl_close($handle);
    if (isset($stream) && is_resource($stream)) {
        fclose($stream);
    }
    if ($raw === false || $error !== '') {
        throw new RuntimeException('Falló una solicitud HTTP local.');
    }
    $headers = $output === null ? substr((string) $raw, 0, $headerSize) : '';
    $body = $output === null ? substr((string) $raw, $headerSize) : '';
    preg_match('/^Location:\s*(.+)$/mi', $headers, $location);
    return ['status' => $status, 'body' => $body, 'location' => trim($location[1] ?? ''), 'type' => $type];
};
$csrf = static function (string $html): string {
    if (!preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $match)) {
        throw new RuntimeException('No se encontró CSRF en la respuesta local.');
    }
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
};

try {
    $template = dirname(__DIR__, 2) . '/docs/rail/consist/referencias/Plantilla_Consist.xlsm';
    foreach ([
        'vehicle_load_report' => '  Vehicle Load Report',
        'shippers' => 'Shippers',
        'cnacs' => 'CNACS',
    ] as $field => $sheetName) {
        $reader = IOFactory::createReaderForFile($template);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);
        $book = $reader->load($template);
        $path = $temp . '/' . $field . '.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $book->disconnectWorksheets();
        $inputs[$field] = $path;
    }

    $login = $request($base . '/index.php?modulo=auth', $cookie);
    $logged = $request($base . '/index.php?modulo=auth', $cookie, [
        '_csrf' => $csrf($login['body']),
        'return' => '/Ferrocheck/public/index.php?modulo=rail',
        'usuario' => $account['usuario'],
        'password' => $password,
    ]);
    if ($logged['status'] !== 303) {
        throw new RuntimeException('El inicio de sesión local no completó PRG.');
    }
    $registerUrl = $base . '/index.php?modulo=rail&seccion=consist-rail&subseccion=registrar';
    $register = $request($registerUrl, $cookie);
    if ($register['status'] !== 200) {
        throw new RuntimeException('No fue posible abrir Nuevo Consist.');
    }
    $upload = $request($registerUrl, $cookie, [
        '_csrf' => $csrf($register['body']),
        'vehicle_load_report' => new CURLFile($inputs['vehicle_load_report']),
        'shippers' => new CURLFile($inputs['shippers']),
        'cnacs' => new CURLFile($inputs['cnacs']),
    ]);
    if ($upload['status'] !== 303 || !preg_match('/preview=([a-f0-9]{64})/', $upload['location'], $tokenMatch)) {
        throw new RuntimeException('La carga local no produjo un lote válido.');
    }
    $previewUrl = str_starts_with($upload['location'], 'http') ? $upload['location'] : 'http://127.0.0.1:8099' . $upload['location'];
    $preview = $request($previewUrl, $cookie);
    $token = $tokenMatch[1];
    $analyze = $request($registerUrl, $cookie, [
        '_csrf' => $csrf($preview['body']), 'action' => 'analyze_vin_cross', 'batch_token' => $token,
    ]);
    if ($analyze['status'] !== 303) {
        throw new RuntimeException('El análisis local no completó PRG.');
    }
    $analysis = $request($previewUrl, $cookie);
    $create = $request($registerUrl, $cookie, [
        '_csrf' => $csrf($analysis['body']),
        'action' => 'create_consist_draft',
        'batch_token' => $token,
        'fecha_inicio' => '2026-07-29',
        'fecha_fin' => '2026-07-30',
    ]);
    if ($create['status'] !== 303 || !preg_match('/[?&]id=(\d+)/', $create['location'], $idMatch)) {
        throw new RuntimeException('El borrador local no fue creado.');
    }
    $id = (int) $idMatch[1];
    $detailUrl = $base . '/index.php?modulo=rail&seccion=consist-rail&subseccion=consultar&id=' . $id;
    $detail = $request($detailUrl, $cookie);
    if ($detail['status'] !== 200 || !str_contains($detail['body'], 'Exportar Consist Rail')) {
        throw new RuntimeException('El detalle del borrador no quedó disponible.');
    }
    $vin = (string) $pdo->query('SELECT vin FROM rail_consist_units WHERE consist_id=' . $id . ' ORDER BY global_position LIMIT 1')->fetchColumn();
    $unit = $request($detailUrl . '&vin_detalle=' . rawurlencode($vin), $cookie);
    $history = $request($base . '/index.php?modulo=rail&seccion=consist-rail&subseccion=historial', $cookie);
    $export = $request($detailUrl . '&accion=exportar_consist', $cookie, null, $download);
    if ($unit['status'] !== 200 || $history['status'] !== 200 || $export['status'] !== 200) {
        throw new RuntimeException('Detalle, historial o exportación local fallaron.');
    }
    $second = $request($detailUrl . '&accion=exportar_consist', $cookie, null, $temp . '/second-download.xlsx');
    if ($second['status'] !== 200) {
        throw new RuntimeException('La segunda descarga local falló.');
    }
    echo json_encode([
        'login' => 'PASS', 'upload' => 'PASS', 'analysis' => 'PASS', 'prg' => 'PASS',
        'consist_id' => $id, 'detail' => 'PASS', 'history' => 'PASS', 'second_download' => 'PASS',
        'download' => $download, 'download_sha256' => hash_file('sha256', $download),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
} finally {
    $pdo->prepare('UPDATE usuarios SET password_hash=:hash WHERE id=:id')
        ->execute(['hash' => $account['password_hash'], 'id' => $account['id']]);
    $pdo->prepare(
        "UPDATE usuario_sesiones SET revocada_at=CURRENT_TIMESTAMP(6),revocada_por=:id,motivo_revocacion='validacion_local_finalizada'
         WHERE usuario_id=:id AND revocada_at IS NULL"
    )->execute(['id' => $account['id']]);
    $password = str_repeat("\0", strlen($password));
}
