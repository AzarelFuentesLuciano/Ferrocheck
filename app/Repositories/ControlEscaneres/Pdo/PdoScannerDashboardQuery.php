<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Pdo;
use App\DTO\ControlEscaneres\{DashboardRange,ScannerAttentionItem,ScannerDashboardResult,ScannerIncidentSummary,ScannerInventorySummary,ScannerRecentActivity,ScannerStatusSummary,ScannerTrendPoint};
use App\Repositories\ControlEscaneres\Contracts\ScannerDashboardQueryInterface;
final class PdoScannerDashboardQuery extends AbstractPdoRepository
implements ScannerDashboardQueryInterface
{
    private const OPEN="estado NOT IN ('resuelta','descartada')";
    public function fetch(DashboardRange$r):ScannerDashboardResult
    {
        $a=$this->stmt("SELECT COUNT(*) total,SUM(CASE WHEN activo=1 THEN 1 ELSE 0 END) active,SUM(CASE WHEN activo=0 THEN 1 ELSE 0 END) inactive,SUM(CASE WHEN activo=1 AND estado='disponible' AND NOT EXISTS(SELECT 1 FROM scanner_movimientos m WHERE m.scanner_id=scanners.id AND m.estado IN('abierto','vencido','con_incidencia')) THEN 1 ELSE 0 END) available,SUM(CASE WHEN estado='entregado' THEN 1 ELSE 0 END) delivered,SUM(CASE WHEN estado='mantenimiento' THEN 1 ELSE 0 END) maintenance FROM scanners")->fetch(\PDO::FETCH_ASSOC)?:[];
        $i=$this->stmt("SELECT COUNT(*) open_count,COUNT(DISTINCT scanner_id) affected,SUM(CASE WHEN severidad='critica' THEN 1 ELSE 0 END) critical FROM scanner_incidencias WHERE ".self::OPEN)->fetch(\PDO::FETCH_ASSOC)?:[];
        $statuses=array_map(fn($x)=>new ScannerStatusSummary($x['estado'],(int)$x['total']),$this->stmt('SELECT estado,COUNT(*) total FROM scanners GROUP BY estado ORDER BY estado')->fetchAll(\PDO::FETCH_ASSOC));
        $p=$this->params($r);$deliveries=(int)$this->stmt('SELECT COUNT(*) FROM scanner_movimientos WHERE entregado_at>=:from AND entregado_at<=:to',$p)->fetchColumn();$receptions=(int)$this->stmt('SELECT COUNT(*) FROM scanner_movimientos WHERE recibido_at>=:from AND recibido_at<=:to',$p)->fetchColumn();
        return new ScannerDashboardResult($r,new ScannerInventorySummary((int)($a['total']??0),(int)($a['active']??0),(int)($a['inactive']??0),(int)($a['available']??0),(int)($a['delivered']??0),(int)($a['maintenance']??0)),new ScannerIncidentSummary((int)($i['open_count']??0),(int)($i['affected']??0),(int)($i['critical']??0)),$statuses,$this->attention(),$this->activity($r),$this->trend($r),$deliveries,$receptions);
    }
    private function params(DashboardRange$r):array{return['from'=>$r->from->format('Y-m-d H:i:s.u'),'to'=>$r->to->format('Y-m-d H:i:s.u')];}
    private function attention():array
    {
        $sql="SELECT s.id,s.codigo,s.estado,s.updated_at,MAX(CASE WHEN i.severidad='critica' AND i.estado NOT IN('resuelta','descartada') THEN i.reportada_at END) critical_at FROM scanners s LEFT JOIN scanner_incidencias i ON i.scanner_id=s.id WHERE s.estado IN('extraviado','pendiente_reparacion') OR EXISTS(SELECT 1 FROM scanner_incidencias x WHERE x.scanner_id=s.id AND x.severidad='critica' AND x.estado NOT IN('resuelta','descartada')) GROUP BY s.id,s.codigo,s.estado,s.updated_at LIMIT 10";
        $out=[];foreach($this->stmt($sql)->fetchAll(\PDO::FETCH_ASSOC)as$x){$critical=$x['critical_at']!==null;$out[]=new ScannerAttentionItem((int)$x['id'],$x['codigo'],$critical?'incidencia_critica':$x['estado'],$critical?'critica':($x['estado']==='extraviado'?'alta':'media'),new \DateTimeImmutable($critical?$x['critical_at']:$x['updated_at']));}usort($out,fn($x,$y)=>['critica'=>1,'alta'=>2,'media'=>3][$x->severity]<=>['critica'=>1,'alta'=>2,'media'=>3][$y->severity]);return$out;
    }
    private function activity(DashboardRange$r):array
    {
        $p=$this->params($r)+['from_received'=>$r->from->format('Y-m-d H:i:s.u'),'to_received'=>$r->to->format('Y-m-d H:i:s.u')];$out=[];$sql='SELECT m.scanner_id,s.codigo,m.folio,m.entregado_at,m.recibido_at FROM scanner_movimientos m JOIN scanners s ON s.id=m.scanner_id WHERE (m.entregado_at>=:from AND m.entregado_at<=:to) OR (m.recibido_at>=:from_received AND m.recibido_at<=:to_received) ORDER BY m.entregado_at DESC LIMIT 10';
        foreach($this->stmt($sql,$p)->fetchAll(\PDO::FETCH_ASSOC)as$x){if($x['entregado_at']>=$p['from']&&$x['entregado_at']<=$p['to'])$out[]=new ScannerRecentActivity((int)$x['scanner_id'],$x['codigo'],'entrega',new \DateTimeImmutable($x['entregado_at']),$x['folio']);if($x['recibido_at']!==null&&$x['recibido_at']>=$p['from_received']&&$x['recibido_at']<=$p['to_received'])$out[]=new ScannerRecentActivity((int)$x['scanner_id'],$x['codigo'],'recepcion',new \DateTimeImmutable($x['recibido_at']),$x['folio']);}
        foreach($this->stmt('SELECT i.scanner_id,s.codigo,i.reportada_at FROM scanner_incidencias i JOIN scanners s ON s.id=i.scanner_id WHERE i.reportada_at>=:from AND i.reportada_at<=:to ORDER BY i.reportada_at DESC LIMIT 10',$this->params($r))->fetchAll(\PDO::FETCH_ASSOC)as$x)$out[]=new ScannerRecentActivity((int)$x['scanner_id'],$x['codigo'],'incidencia',new \DateTimeImmutable($x['reportada_at']));usort($out,fn($x,$y)=>$y->occurredAt<=>$x->occurredAt);return array_slice($out,0,10);
    }
    private function trend(DashboardRange$r):array
    {
        $points=[];for($d=$r->from->setTime(0,0);$d<=$r->to;$d=$d->modify('+1 day'))$points[$d->format('Y-m-d')]=[0,0,0];$p=$this->params($r);
        $movementParams=$p+['from_received'=>$p['from'],'to_received'=>$p['to']];foreach($this->stmt('SELECT entregado_at,recibido_at FROM scanner_movimientos WHERE (entregado_at>=:from AND entregado_at<=:to) OR (recibido_at>=:from_received AND recibido_at<=:to_received)',$movementParams)->fetchAll(\PDO::FETCH_ASSOC)as$x){$day=substr($x['entregado_at'],0,10);if(isset($points[$day]))$points[$day][0]++;if($x['recibido_at']!==null){$day=substr($x['recibido_at'],0,10);if(isset($points[$day]))$points[$day][1]++;}}
        foreach($this->stmt('SELECT reportada_at FROM scanner_incidencias WHERE reportada_at>=:from AND reportada_at<=:to',$p)->fetchAll(\PDO::FETCH_ASSOC)as$x){$day=substr($x['reportada_at'],0,10);if(isset($points[$day]))$points[$day][2]++;}return array_map(fn($date,$x)=>new ScannerTrendPoint($date,$x[0],$x[1],$x[2]),array_keys($points),$points);
    }
}
