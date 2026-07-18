<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__, 2);
$read = static fn(string $path): string => (string) file_get_contents($root . '/' . $path);
$render = static function (string $component, array $data) use ($root): string { extract($data, EXTR_SKIP); ob_start(); require $root . '/app/Views/components/' . $component . '.php'; return (string) ob_get_clean(); };

$steps = $render('process-steps', ['processSteps' => ['Equipo', '<script>', 'Confirmación'], 'processCurrent' => 2]);
test('pasos escapan contenido y anuncian paso actual', fn() => ok(!str_contains($steps, '<script>') && str_contains($steps, 'aria-current="step"')));
$summary = $render('operation-summary', ['operationItems' => ['Equipo' => '<img>'], 'operationMessage' => '<script>']);
test('resumen de operación escapa datos', fn() => ok(!str_contains($summary, '<img>') && !str_contains($summary, '<script>')));
$comparison = $render('inspection-comparison', ['comparisonItems' => [['label' => 'Pantalla', 'result' => '"><script>', 'message' => '<b>']]]);
test('comparación limita variantes y escapa valores', fn() => ok(str_contains($comparison, 'no_comparable') && !str_contains($comparison, '<script>') && !str_contains($comparison, '<b>')));
$timeline = $render('timeline-entry', ['timelineEntry' => ['at' => '<time>', 'title' => '<b>', 'description' => '<script>']]);
test('timeline escapa actividad', fn() => ok(!str_contains($timeline, '<script>') && !str_contains($timeline, '<b>')));

$delivery = $read('app/Views/control-escaneres/entrega.php');
$receipt = $read('app/Views/control-escaneres/recepcion.php');
$incidents = $read('app/Views/control-escaneres/incidencias.php');
$maintenance = $read('app/Views/control-escaneres/mantenimiento.php');
$record = $read('app/Views/control-escaneres/expediente.php');
$history = $read('app/Views/control-escaneres/historial.php');
$dashboard = $read('app/Views/control-escaneres/dashboard.php');
test('entrega conserva contrato y CSRF', fn() => ok(str_contains($delivery, 'name="_csrf"') && str_contains($delivery, 'name="scanner_id"') && str_contains($delivery, 'name="person_name"') && str_contains($delivery, 'name="employee_number"')));
test('recepción conserva contrato y CSRF', fn() => ok(str_contains($receipt, 'name="_csrf"') && str_contains($receipt, 'name="movement_id"') && str_contains($receipt, 'name="battery"')));
test('incidencias conserva reporte y resolución', fn() => ok(str_contains($incidents, 'value="report"') && str_contains($incidents, 'value="resolve"') && str_contains($incidents, 'name="resulting_status"')));
test('mantenimiento conserva transiciones', fn() => ok(str_contains($maintenance, "'return' : 'send'") && str_contains($maintenance, 'name="reason"') && str_contains($maintenance, 'name="observations"')));
test('formularios tienen labels vinculados', fn() => ok(substr_count($incidents, 'for="') >= 5 && substr_count($maintenance, 'for="') >= 3));
test('acciones POST tienen una primaria por bloque', fn() => ok(substr_count($maintenance, 'type="submit"') === 1 && substr_count($incidents, 'type="submit"') === 2));
test('expediente presenta datos protegidos', fn() => ok(str_contains($record, 'IMEI protegido') && str_contains($record, 'ICCID protegido') && !preg_match('/\b(PIN|PUK)\b/', $record)));
test('historial no contiene registros simulados', fn() => ok(!preg_match('/ESC-\d{3}/', $history) && str_contains($history, 'Vista provisional')));
test('dashboard usa datos reales sin métricas simuladas', fn() => ok(!preg_match('/ESC-\d{3}|\b(?:48|96)%\b/', $dashboard) && str_contains($dashboard, '$dashboardViewModel') && !str_contains($dashboard, "foreach ([['")));
$operationsCss = $read('public/assets/css/control-escaneres/operations.css');
$historyCss = $read('public/assets/css/control-escaneres/history.css');
test('operaciones e historial responden en móvil', fn() => ok(str_contains($operationsCss, '@media(max-width:600px)') && str_contains($historyCss, '@media(max-width:800px)')));
$operationsJs = $read('public/assets/js/control-escaneres/operations-ui.js');
test('JS progresivo previene doble envío', fn() => ok(str_contains($operationsJs, "addEventListener('submit'") && str_contains($operationsJs, 'disabled=true')));
test('JS no ejecuta solicitudes de negocio', fn() => ok(!preg_match('/\b(fetch|XMLHttpRequest|axios)\b/', $operationsJs)));
finish('UX Operations');
