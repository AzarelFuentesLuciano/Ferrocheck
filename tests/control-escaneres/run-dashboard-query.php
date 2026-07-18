<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\DTO\ControlEscaneres\DashboardRange;use App\Repositories\ControlEscaneres\Pdo\PdoScannerDashboardQuery;
function dashboardDb():PDO{$p=new PDO('sqlite::memory:');$p->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$p->exec("CREATE TABLE scanners(id INTEGER PRIMARY KEY,codigo TEXT,estado TEXT,activo INTEGER,updated_at TEXT);CREATE TABLE scanner_movimientos(id INTEGER PRIMARY KEY,scanner_id INTEGER,folio TEXT,estado TEXT,entregado_at TEXT,recibido_at TEXT);CREATE TABLE scanner_incidencias(id INTEGER PRIMARY KEY,scanner_id INTEGER,severidad TEXT,estado TEXT,reportada_at TEXT);");return$p;}
function seedDashboard(PDO$p):void{$p->exec("INSERT INTO scanners VALUES(1,'SC-0001','disponible',1,'2026-07-18 08:00:00'),(2,'SC-0002','entregado',1,'2026-07-18 08:00:00'),(3,'SC-0003','mantenimiento',1,'2026-07-17 08:00:00'),(4,'SC-0004','extraviado',0,'2026-07-16 08:00:00'),(5,'SC-0005','pendiente_reparacion',1,'2026-07-15 08:00:00');INSERT INTO scanner_movimientos VALUES(1,2,'MOV-1','abierto','2026-07-18 09:00:00',NULL),(2,1,'MOV-2','devuelto','2026-07-10 09:00:00','2026-07-18 10:00:00');INSERT INTO scanner_incidencias VALUES(1,5,'critica','abierta','2026-07-18 11:00:00'),(2,5,'alta','en_revision','2026-07-17 11:00:00'),(3,5,'critica','resuelta','2026-07-16 11:00:00');");}
$now=new DateTimeImmutable('2026-07-18 12:00:00',new DateTimeZone('America/Mexico_City'));
test('dashboard vacío',function()use($now){$r=(new PdoScannerDashboardQuery(dashboardDb()))->fetch(DashboardRange::fromInput('today',$now));same(0,$r->inventory->total);same(0,$r->incidents->openIncidents);});
$pdo=dashboardDb();seedDashboard($pdo);$query=new PdoScannerDashboardQuery($pdo);$today=$query->fetch(DashboardRange::fromInput('today',$now));
test('conteo total activo inactivo',fn()=>ok($today->inventory->total===5&&$today->inventory->active===4&&$today->inventory->inactive===1));
test('estados canónicos',fn()=>ok($today->inventory->available===1&&$today->inventory->delivered===1&&$today->inventory->maintenance===1));
test('incidencias sin duplicar equipos',fn()=>ok($today->incidents->openIncidents===2&&$today->incidents->affectedScanners===1));
test('críticas excluyen resueltas',fn()=>same(1,$today->incidents->criticalIncidents));
test('entregas del día',fn()=>same(1,$today->deliveriesInRange));
test('recepciones incluyen entrega anterior',fn()=>same(1,$today->receptionsInRange));
test('atención deduplica escáner',fn()=>same(2,count($today->attention)));
test('prioridad crítica primero',fn()=>same('critica',$today->attention[0]->severity));
test('actividad ordenada',fn()=>ok($today->activity[0]->occurredAt>=$today->activity[1]->occurredAt));
test('actividad limitada',fn()=>ok(count($today->activity)<=10));
test('tendencia hoy',fn()=>ok(count($today->trend)===1&&$today->trend[0]->deliveries===1&&$today->trend[0]->receptions===1&&$today->trend[0]->incidents===1));
$seven=$query->fetch(DashboardRange::fromInput('7d',$now));test('rango siete días',fn()=>ok(count($seven->trend)===7&&$seven->incidents->openIncidents===2));
$thirty=$query->fetch(DashboardRange::fromInput('30d',$now));test('rango treinta días',fn()=>same(30,count($thirty->trend)));
test('allowlist inválida vuelve a hoy',fn()=>same('today',DashboardRange::fromInput('DROP TABLE',$now)->key));
test('movimiento abierto impide disponible',function()use($now){$p=dashboardDb();$p->exec("INSERT INTO scanners VALUES(1,'SC-1','disponible',1,'2026-07-18');INSERT INTO scanner_movimientos VALUES(1,1,'M','abierto','2026-07-18',NULL)");$r=(new PdoScannerDashboardQuery($p))->fetch(DashboardRange::fromInput('today',$now));same(0,$r->inventory->available);});
finish('Dashboard Query');
