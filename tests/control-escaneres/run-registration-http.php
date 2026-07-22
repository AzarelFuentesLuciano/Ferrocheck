<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

use App\Core\Database;

const REGISTRATION_URL = 'http://127.0.0.1/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=registrar';

function request(string $url, string $sessionId, string $method = 'GET', array $data = []): array
{
    $headers = ['Cookie: PHPSESSID=' . $sessionId];
    $options = ['method' => $method, 'ignore_errors' => true, 'follow_location' => 0];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $options['content'] = http_build_query($data);
    }
    $options['header'] = implode("\r\n", $headers);
    $body = file_get_contents($url, false, stream_context_create(['http' => $options]));
    $responseHeaders = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $status);
    return [(int) ($status[1] ?? 0), (string) $body, $responseHeaders];
}

function csrf(string $html): string
{
    preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $match);
    return html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8');
}

function seedSession(string $sessionId, ?int $userId): void
{
    session_id($sessionId);
    session_start();
    if ($userId !== null) {
        $_SESSION['user_id'] = $userId;
    }
    session_write_close();
}

$pdo = Database::getConnection();
$areaId = (int) $pdo->query('SELECT id FROM scanner_areas WHERE activo = 1 ORDER BY id LIMIT 1')->fetchColumn();
if ($areaId < 1) {
    throw new RuntimeException('Se requiere al menos un área activa para la prueba HTTP.');
}

$suffix = strtoupper(bin2hex(random_bytes(4)));
$tag = 'HTTP-' . $suffix;
$sessionId = 'cereg' . strtolower($suffix);
$anonymousSessionId = 'ceanon' . strtolower($suffix);
$scannerId = null;

try {
    seedSession($anonymousSessionId, null);
    seedSession($sessionId, 900001);
    [$anonymousGetStatus, $anonymousHtml] = request(REGISTRATION_URL, $anonymousSessionId);
    $anonymousToken = csrf($anonymousHtml);
    [$anonymousPostStatus, , $anonymousHeaders] = request(REGISTRATION_URL, $anonymousSessionId, 'POST', [
        '_csrf' => $anonymousToken,
        'tag' => $tag . '-ANON',
        'brand' => 'Zebra',
        'model' => 'TC22',
        'area_id' => $areaId,
        'status' => 'disponible',
        'active' => '1',
    ]);
    [, $anonymousResult] = request(REGISTRATION_URL, $anonymousSessionId);
    test('POST sin sesión se rechaza con PRG y conserva datos', function () use ($anonymousGetStatus, $anonymousPostStatus, $anonymousHeaders, $anonymousResult, $tag): void {
        ok($anonymousGetStatus === 200 && $anonymousPostStatus === 303);
        ok((bool) array_filter($anonymousHeaders, fn(string $header): bool => str_starts_with(strtolower($header), 'location:')));
        ok(str_contains($anonymousResult, $tag . '-ANON') && str_contains($anonymousResult, 'Debe iniciar'));
    });

    [$getStatus, $html] = request(REGISTRATION_URL, $sessionId);
    $token = csrf($html);
    test('GET de alta entrega formulario y CSRF', fn() => ok($getStatus === 200 && strlen($token) === 64));

    [$postStatus, , $postHeaders] = request(REGISTRATION_URL, $sessionId, 'POST', [
        '_csrf' => $token,
        'tag' => ' ' . strtolower($tag) . ' ',
        'brand' => 'Zebra',
        'model' => 'TC22',
        'phone' => '55 1234-5678',
        'area_id' => $areaId,
        'status' => 'disponible',
        'active' => '0',
        'observations' => 'Prueba HTTP controlada',
    ]);
    $location = '';
    foreach ($postHeaders as $header) {
        if (str_starts_with(strtolower($header), 'location:')) {
            $location = trim(substr($header, 9));
        }
    }
    test('POST válido responde PRG hacia Catálogo', fn() => ok($postStatus === 303 && str_contains($location, 'seccion=catalogo')));

    $row = $pdo->query("SELECT id, tag_original, area_id, activo, created_by FROM scanners WHERE tag_original = " . $pdo->quote($tag))->fetch();
    $scannerId = (int) ($row['id'] ?? 0);
    test('POST HTTP inserta datos normalizados y actor', fn() => ok($scannerId > 0 && $row['tag_original'] === $tag && (int) $row['area_id'] === $areaId && (int) $row['activo'] === 0 && (int) $row['created_by'] === 900001));
    test('alta HTTP deja auditoría', fn() => same(1, (int) $pdo->query("SELECT COUNT(*) FROM auditoria_eventos WHERE accion = 'scanner.create' AND entidad_id = " . $scannerId)->fetchColumn()));

    $catalogUrl = 'http://127.0.0.1' . $location . '&q=' . rawurlencode($tag);
    [$catalogStatus, $catalogHtml] = request($catalogUrl, $sessionId);
    test('el alta aparece en Catálogo con confirmación', fn() => ok($catalogStatus === 200 && str_contains($catalogHtml, $tag) && str_contains($catalogHtml, 'registrado correctamente')));
} finally {
    if ($scannerId !== null && $scannerId > 0) {
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM auditoria_eventos WHERE entidad_id = ' . $scannerId . " AND entidad = 'scanner'");
            $pdo->exec('DELETE FROM scanners WHERE id = ' . $scannerId);
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
    }
}

finish('Scanner Registration HTTP');
