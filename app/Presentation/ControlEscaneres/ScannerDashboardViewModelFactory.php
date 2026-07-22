<?php
declare(strict_types=1);
namespace App\Presentation\ControlEscaneres;
use App\DTO\ControlEscaneres\ScannerDashboardResult;
use App\ViewModels\ControlEscaneres\{DashboardActivityItemViewModel,DashboardAlertViewModel,DashboardKpiViewModel,DashboardQuickActionViewModel,DashboardStatusItemViewModel,DashboardTrendPointViewModel,ScannerDashboardViewModel};
final class ScannerDashboardViewModelFactory
{
    private const STATUS=['disponible'=>'Disponible','entregado'=>'Entregado','mantenimiento'=>'Mantenimiento','pendiente_reparacion'=>'Pendiente de reparación','baja_definitiva'=>'Baja definitiva','extraviado'=>'Extraviado'];
    public function create(ScannerDashboardResult$r,string$baseUrl,\DateTimeImmutable$now):ScannerDashboardViewModel
    {
        $catalog=$baseUrl.'/index.php?modulo=control-escaneres&seccion=catalogo';$total=$r->inventory->total;$m=$r->metrics;
        $kpis=[
            new DashboardKpiViewModel('total','Total',$total,$r->inventory->active.' activos','neutral',$catalog),
            new DashboardKpiViewModel('available','Disponibles',$r->inventory->available,'Listos para asignar','success',$catalog.'&estado=disponible'),
            new DashboardKpiViewModel('delivered','Entregados',$r->inventory->delivered,'En custodia operativa','info',$catalog.'&estado=entregado'),
            new DashboardKpiViewModel('pending','Pendientes por regresar',(int)($m['pending_return']??$r->inventory->delivered),'Movimientos abiertos','warning',$catalog.'&estado=entregado'),
            new DashboardKpiViewModel('incidents','Incidencias abiertas',$r->incidents->openIncidents,$r->incidents->affectedScanners.' equipos afectados',$r->incidents->criticalIncidents>0?'warning':'neutral',$catalog.'&incidencia=1'),
            new DashboardKpiViewModel('maintenance','Mantenimiento',$r->inventory->maintenance,'En atención técnica','neutral',$catalog.'&estado=mantenimiento'),
            new DashboardKpiViewModel('repair','Pendiente de reparación',(int)($m['pending_repair']??0),'Requieren intervención','warning',$catalog.'&estado=pendiente_reparacion'),
            new DashboardKpiViewModel('lost','Extraviados',(int)($m['lost']??0),'Seguimiento prioritario','warning',$catalog.'&estado=extraviado'),
            new DashboardKpiViewModel('retired','Baja definitiva',(int)($m['retired']??0),'Fuera de operación','neutral',$catalog.'&activo=0'),
            new DashboardKpiViewModel('received','Recibidos en el periodo',$r->receptionsInRange,'Recepciones confirmadas','success',$catalog),
            new DashboardKpiViewModel('no-photo','Sin fotografía',(int)($m['without_photo']??0),'Expediente incompleto','neutral',$catalog),
            new DashboardKpiViewModel('no-qr','Sin QR',(int)($m['without_qr']??0),'Identidad por completar','neutral',$catalog),
        ];
        $statuses=array_map(fn($x)=>new DashboardStatusItemViewModel(self::STATUS[$x->status]??ucwords(str_replace('_',' ',$x->status)),$x->count,$total>0?round($x->count*100/$total,1):0.0,$x->status),$r->statuses);
        $situations=['incidencia_critica'=>'Incidencia crítica abierta','extraviado'=>'Equipo marcado como extraviado','pendiente_reparacion'=>'Equipo pendiente de reparación'];
        $alerts=array_map(fn($x)=>new DashboardAlertViewModel($x->scannerCode,$situations[$x->situation]??'Requiere atención',$x->severity,$x->occurredAt->format('d/m/Y H:i'),$baseUrl.'/index.php?modulo=control-escaneres&seccion=expediente&scanner_id='.$x->scannerId),$r->attention);
        $actions=['entrega'=>'Entrega registrada','recepcion'=>'Recepción registrada','incidencia'=>'Incidencia reportada'];
        $activity=array_map(fn($x)=>new DashboardActivityItemViewModel($actions[$x->type]??'Actividad operativa',$x->scannerCode,$x->occurredAt->format('d/m/Y H:i'),$x->folio,$baseUrl.'/index.php?modulo=control-escaneres&seccion=expediente&scanner_id='.$x->scannerId),$r->activity);
        $trend=array_map(fn($x)=>new DashboardTrendPointViewModel($x->date,(new \DateTimeImmutable($x->date))->format('d/m'),$x->deliveries,$x->receptions,$x->incidents),$r->trend);
        $hasTrend=array_sum(array_map(fn($x)=>$x->deliveries+$x->receptions+$x->incidents,$trend))>0;
        $quick=[new DashboardQuickActionViewModel('Ver catálogo','Localiza un equipo y consulta su estado.',$catalog),new DashboardQuickActionViewModel('Registrar entrega','Selecciona primero un equipo disponible.',$catalog.'&estado=disponible'),new DashboardQuickActionViewModel('Registrar recepción','Selecciona primero un equipo entregado.',$catalog.'&estado=entregado'),new DashboardQuickActionViewModel('Reportar incidencia','Selecciona el equipo afectado.',$catalog),new DashboardQuickActionViewModel('Consultar reportes','Filtra y exporta información operativa.',$baseUrl.'/index.php?modulo=control-escaneres&seccion=reporte')];
        return new ScannerDashboardViewModel($r->range->key,['today'=>'Hoy','7d'=>'Últimos 7 días','30d'=>'Últimos 30 días'][$r->range->key],$now->format('d/m/Y H:i'),$kpis,$statuses,$alerts,$activity,$trend,$quick,$r->deliveriesInRange,$r->receptionsInRange,$hasTrend,$r->analytics);
    }
}
