<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Rail\Consist\ConsistDocumentBuilder;
use App\Services\Rail\Consist\ConsistOperationalPeriod;
use App\Services\Rail\Consist\RouteCodeResolver;

$passed = 0;
$failed = 0;
$test = static function (string $label, callable $assertion) use (&$passed, &$failed): void {
    try { $ok = $assertion() === true; } catch (Throwable $exception) {
        $ok = false; $label .= ' (' . $exception::class . ': ' . $exception->getMessage() . ')';
    }
    $ok ? $passed++ : $failed++;
    echo sprintf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
};
$throws = static function (callable $callback, string $message): bool {
    try { $callback(); } catch (Throwable $exception) { return str_contains($exception->getMessage(), $message); }
    return false;
};
$resolver = new RouteCodeResolver();
$catalog = static fn (string $code, array $rows): array => ['route_variants' => [$code => $rows]];

$one = [['source_row'=>9,'route_code'=>'ONE','market'=>'NAFTA','shipping_destination'=>'DESTINO','carrier'=>'A']];
$test('Route Code con una fila usa modo unique', static fn (): bool =>
    $resolver->resolve($catalog('ONE', $one), 'one', 'X')['resolution_mode'] === 'unique');

$equivalent = [
    ['source_row'=>8,'id'=>2,'route_code'=>'EQ','market'=>'NAFTA','shipping_destination'=>'DEST','carrier'=>'A'],
    ['source_row'=>3,'id'=>7,'route_code'=>'EQ','market'=>'nafta','shipping_destination'=>' DEST ','carrier'=>'B'],
];
$equivalentResult = $resolver->resolve($catalog('EQ', $equivalent), 'EQ', 'X');
$test('duplicados equivalentes seleccionan menor source_row', static fn (): bool =>
    $equivalentResult['resolution_mode'] === 'equivalent_duplicates'
    && $equivalentResult['selected_source_row'] === 3 && $equivalentResult['variant_count'] === 2);

$ambiguous = [
    ['source_row'=>20,'route_code'=>'K','market'=>'NAFTA','shipping_destination'=>'A','carrier'=>'BNSF'],
    ['source_row'=>21,'route_code'=>'K','market'=>'NAFTA','shipping_destination'=>'B','carrier'=>'UP'],
];
$kcsm = $resolver->resolve($catalog('K', $ambiguous), 'K', ' kcsm ', 'irrelevante');
$test('KCSM fuerza Laredo sin depender del Carrier de catálogo', static fn (): bool =>
    $kcsm['resolution_mode'] === 'kcsm_override' && $kcsm['shipping_destination'] === 'Laredo');

$official = [
    '54' => [
        ['source_row'=>160,'route_code'=>'54','route_king'=>'83','market'=>'NAFTA','shipping_destination'=>'ORILLIA','carrier'=>'BNSF','production_plant'=>'RAM','rc_plant'=>'83RAM'],
        ['source_row'=>258,'route_code'=>'54','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>263,'route_code'=>'54','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>271,'route_code'=>'54','route_king'=>'54','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
    ],
    '54B' => [
        ['source_row'=>161,'route_code'=>'54B','route_king'=>'83','market'=>'NAFTA','shipping_destination'=>'ORILLIA','carrier'=>'BNSF','production_plant'=>'RAM','rc_plant'=>'83RAM'],
        ['source_row'=>259,'route_code'=>'54B','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>260,'route_code'=>'54B','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>264,'route_code'=>'54B','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
    ],
    '54D' => [
        ['source_row'=>163,'route_code'=>'54D','route_king'=>'83','market'=>'NAFTA','shipping_destination'=>'ORILLIA','carrier'=>'BNSF','production_plant'=>'RAM','rc_plant'=>'83RAM'],
        ['source_row'=>261,'route_code'=>'54D','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>262,'route_code'=>'54D','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
        ['source_row'=>265,'route_code'=>'54D','route_king'=>'53','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'BNSF','production_plant'=>'Promaster','rc_plant'=>'53Promaster'],
    ],
];
$observed = [
    ['3C6RRFGGXT4209760','54B'], ['3C6SRFJP2T4195055','54D'],
    ['3C6UR5CJ9VG381544','54'], ['3C7WRNDJ1VG384702','54'],
    ['3C6UR5CJ0VG381545','54'], ['3C6UR5CJ3VG381541','54'],
    ['3C6SRFLP0T4215929','54D'], ['3C6UR5CJ5VG381542','54'],
];
foreach ($observed as [$vin, $code]) {
    $result = $resolver->resolve($catalog($code, $official[$code]), $code, 'XFXE', 'Orillia, Washington');
    $test("VIN histórico {$vin} reproduce NAFTA/ORILLIA", static fn (): bool =>
        $result['market'] === 'NAFTA' && $result['shipping_destination'] === 'ORILLIA'
        && $result['resolution_mode'] === 'destination_match' && $result['warning'] === null);
}
$test('XFXE no filtra las filas BNSF', static fn (): bool =>
    $resolver->resolve($catalog('54', $official['54']), '54', 'XFXE', 'Orillia, Washington')['selected_source_row'] === 160);

$route75 = [
    ['source_row'=>288,'route_code'=>'75','market'=>'NAFTA','shipping_destination'=>'Amarillo, TX','carrier'=>'BNSF'],
    ['source_row'=>289,'route_code'=>'75','market'=>'NAFTA','shipping_destination'=>'Laredo','carrier'=>'BNSF'],
];
$fallback75 = $resolver->resolve($catalog('75', $route75), '75', 'XFXE', 'Destino desconocido');
$test('75 aplica fallback por menor source_row y genera warning', static fn (): bool =>
    $fallback75['resolution_mode'] === 'historical_first_match' && $fallback75['selected_source_row'] === 288
    && ($fallback75['warning']['type'] ?? '') === 'route_code_historical_fallback');

$route59 = [
    ['source_row'=>129,'route_code'=>'59','market'=>'NAFTA','shipping_destination'=>'','carrier'=>'UP'],
    ['source_row'=>158,'route_code'=>'59','market'=>'NAFTA','shipping_destination'=>'ORILLIA','carrier'=>'BNSF'],
];
$test('59 usa destino cuando aporta evidencia única', static fn (): bool =>
    $resolver->resolve($catalog('59', $route59), '59', 'XFXE', 'Orillia, Washington')['resolution_mode'] === 'destination_match');
$test('59 usa fallback cuando el destino no aporta evidencia', static fn (): bool =>
    $resolver->resolve($catalog('59', $route59), '59', 'XFXE', '')['selected_source_row'] === 129);

$routeAr = [
    ['source_row'=>388,'route_code'=>'AR','market'=>'BUX','shipping_destination'=>'Altamira','carrier'=>'JCC'],
    ['source_row'=>389,'route_code'=>'AR','market'=>'BUX','shipping_destination'=>'Verificar Destino en Solicitud de BUX','carrier'=>'OTRO'],
];
$test('AR se resuelve por localidad conservadora', static fn (): bool =>
    $resolver->resolve($catalog('AR', $routeAr), 'AR', 'XFXE', 'Puerto de Altamira, Tamaulipas')['selected_source_row'] === 388);
$routeBr = [
    ['source_row'=>391,'route_code'=>'BR','market'=>'BUX','shipping_destination'=>'Truck','carrier'=>'JCC'],
    ['source_row'=>392,'route_code'=>'BR','market'=>'BUX','shipping_destination'=>'Verificar Destino en Solicitud de BUX','carrier'=>'OTRO'],
];
$test('BR conserva fallback histórico cuando no hay coincidencia', static fn (): bool =>
    $resolver->resolve($catalog('BR', $routeBr), 'BR', 'XFXE', 'Sin localidad')['resolution_mode'] === 'historical_first_match');
$test('Route Code inexistente conserva excepción controlada', static fn (): bool =>
    $throws(static fn () => $resolver->resolve([], 'NO-EXISTE', 'XFXE', ''), 'NO-EXISTE'));

$analysisUnit = static fn (string $vin, string $code, string $destination): array => [
    'vin'=>$vin, 'presence'=>['vehicle_load_report'=>true,'shippers'=>true,'cnacs'=>true],
    'duplicate_counts'=>['cnacs'=>0], 'source_rows'=>['vehicle_load_report'=>2],
    'source_data'=>[
        'vehicle_load_report'=>['fdwholevin'=>$vin,'fdmanufacturerroutecode'=>$code,'fdSCAC'=>'XFXE','fdDestinationLocation'=>$destination,'fdTransportationName1'=>'P1','fdTrack'=>'T1'],
        'shippers'=>['Textbox7'=>'S1'], 'cnacs'=>['Pedimento'=>'P1'],
    ],
];
$builderCatalog = ['version'=>['source_filename'=>'fixture','source_sha256'=>str_repeat('a',64)],'route_variants'=>['75'=>$route75,'54'=>$official['54']]];
$draft = (new ConsistDocumentBuilder())->build(str_repeat('a',64), ['units'=>[
    $analysisUnit('VIN-FALLBACK','75','No coincide'), $analysisUnit('VIN-DESTINO','54','Orillia, Washington'),
]], $builderCatalog, 1, ConsistOperationalPeriod::fromInput('2026-07-29','2026-07-30'))->toArray();
$test('fixture completo no se detiene por ambigüedad y agrega resumen', static fn (): bool =>
    $draft['total_units'] === 2
    && ($draft['units'][0]['trace']['route_code_resolution']['mode'] ?? '') === 'historical_first_match'
    && ($draft['issues'][0]['fallback_unit_count'] ?? 0) === 1);
$test('Route Code inexistente impide obtener borrador parcial', static fn (): bool =>
    $throws(static fn () => (new ConsistDocumentBuilder())->build(str_repeat('b',64), ['units'=>[
        $analysisUnit('VIN-VALIDO','54','Orillia, Washington'), $analysisUnit('VIN-INVALIDO','NO-EXISTE',''),
    ]], $builderCatalog, 1, ConsistOperationalPeriod::fromInput('2026-07-29','2026-07-30')), 'NO-EXISTE'));

echo "\nResumen Route Code Resolver: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
