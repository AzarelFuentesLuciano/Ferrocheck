<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\DTO\ControlEscaneres\{DashboardRange,ScannerAttentionItem,ScannerDashboardResult,ScannerIncidentSummary,ScannerInventorySummary,ScannerRecentActivity,ScannerStatusSummary,ScannerTrendPoint};use App\Presentation\ControlEscaneres\ScannerDashboardViewModelFactory;
$now=new DateTimeImmutable('2026-07-18 12:00:00',new DateTimeZone('America/Mexico_City'));$range=DashboardRange::fromInput('7d',$now);$result=new ScannerDashboardResult($range,new ScannerInventorySummary(10,8,2,4,2,1),new ScannerIncidentSummary(3,2,1),[new ScannerStatusSummary('disponible',4),new ScannerStatusSummary('entregado',2)],[new ScannerAttentionItem(7,'SC-0007','incidencia_critica','critica',$now)],[new ScannerRecentActivity(7,'SC-0007','incidencia',$now)],[new ScannerTrendPoint('2026-07-18',1,2,1)],1,2);$vm=(new ScannerDashboardViewModelFactory())->create($result,'/Ferrocheck/public',$now);
test('ViewModel inmutable',fn()=>ok((new ReflectionClass($vm))->isReadOnly()));
test('porcentaje calculado',fn()=>same(40.0,$vm->statuses[0]->percentage));
test('rango y reloj congelado',fn()=>ok($vm->rangeLabel==='Últimos 7 días'&&$vm->updatedAt==='18/07/2026 12:00'));
test('KPI distingue incidencias y equipos',fn()=>ok($vm->kpis[4]->value===3&&str_contains($vm->kpis[4]->context,'2 equipos')));
test('alerta humanizada',fn()=>same('Incidencia crítica abierta',$vm->alerts[0]->situation));
test('alerta prioritaria conserva severidad',fn()=>same('critica',$vm->alerts[0]->severity));
test('actividad sin nombres internos',fn()=>same('Incidencia reportada',$vm->activity[0]->title));
test('acciones rápidas limitadas',fn()=>same(5,count($vm->quickActions)));
test('acciones no eligen escáner',fn()=>ok(!str_contains($vm->quickActions[1]->url,'scanner_id')));
test('tendencia real detectada',fn()=>ok($vm->hasTrend));
$zero=new ScannerDashboardResult(DashboardRange::fromInput('today',$now),new ScannerInventorySummary(0,0,0,0,0,0),new ScannerIncidentSummary(0,0,0),[new ScannerStatusSummary('disponible',0)],[],[],[new ScannerTrendPoint('2026-07-18',0,0,0)],0,0);$empty=(new ScannerDashboardViewModelFactory())->create($zero,'',$now);
test('total cero evita división',fn()=>same(0.0,$empty->statuses[0]->percentage));
test('cero actividad no crea tendencia',fn()=>ok(!$empty->hasTrend));
test('sin secretos en ViewModel',fn()=>ok(!preg_match('/\b(PIN|PUK|IMEI|ICCID|fingerprint|payload)\b/i',serialize($vm))));
finish('Dashboard ViewModels');
