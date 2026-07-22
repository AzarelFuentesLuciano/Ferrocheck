<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Pdo;
use App\DTO\ControlEscaneres\ScannerCatalogFilter;use App\Repositories\ControlEscaneres\Contracts\ScannerCatalogQueryInterface;
final class PdoScannerCatalogQuery extends AbstractPdoRepository implements ScannerCatalogQueryInterface
{
    private function criteria(ScannerCatalogFilter $f):array
    {
        $where=[];$params=[];
        if($f->search!==null){$where[]='(s.codigo LIKE :q1 OR s.tag_original LIKE :q2 OR s.numero_serie LIKE :q3 OR s.imei LIKE :q4 OR s.telefono LIKE :q5 OR s.iccid LIKE :q6 OR s.marca LIKE :q7 OR s.modelo LIKE :q8)';for($i=1;$i<=8;$i++)$params['q'.$i]='%'.$f->search.'%';}
        if($f->brand!==null){$where[]='s.marca LIKE :brand';$params['brand']='%'.$f->brand.'%';}
        if($f->model!==null){$where[]='s.modelo LIKE :model';$params['model']='%'.$f->model.'%';}
        if($f->area!==null){$where[]='s.area_habitual LIKE :area';$params['area']='%'.$f->area.'%';}
        if($f->organizationalArea==='unassigned')$where[]='s.area_organizacional_id IS NULL';elseif($f->organizationalArea!==null){$where[]='s.area_organizacional_id=:organizational_area';$params['organizational_area']=(int)$f->organizationalArea;}
        if($f->status!==null){$where[]='s.estado=:status';$params['status']=$f->status;}
        if($f->active!==null){$where[]='s.activo=:active';$params['active']=(int)$f->active;}
        if($f->withIncident!==null)$where[]=$f->withIncident?"EXISTS(SELECT 1 FROM scanner_incidencias i WHERE i.scanner_id=s.id AND i.estado NOT IN('resuelta','cancelada'))":"NOT EXISTS(SELECT 1 FROM scanner_incidencias i WHERE i.scanner_id=s.id AND i.estado NOT IN('resuelta','cancelada'))";
        return[$where?' WHERE '.implode(' AND ',$where):'',$params];
    }
    public function search(ScannerCatalogFilter$f):array{[$where,$p]=$this->criteria($f);$sql="SELECT s.id,s.codigo,s.tag_original,s.area_habitual,s.marca,s.modelo,s.numero_serie,s.imei,s.telefono,s.iccid,s.estado,s.activo,s.indice_conservacion,s.updated_at,ao.nombre area_organizacional,(SELECT MAX(m.entregado_at) FROM scanner_movimientos m WHERE m.scanner_id=s.id) ultima_entrega,(SELECT m.persona_entrega_nombre FROM scanner_movimientos m WHERE m.scanner_id=s.id AND m.estado IN('abierto','vencido','con_incidencia') ORDER BY m.entregado_at DESC LIMIT 1) responsable_actual,EXISTS(SELECT 1 FROM scanner_incidencias i WHERE i.scanner_id=s.id AND i.estado NOT IN('resuelta','cancelada')) incidencia_abierta FROM scanners s LEFT JOIN areas_organizacionales ao ON ao.id=s.area_organizacional_id".$where.' ORDER BY s.'.$f->orderBy.' '.$f->direction.' LIMIT '.(int)$f->perPage.' OFFSET '.(int)$f->offset();return$this->stmt($sql,$p)->fetchAll(\PDO::FETCH_ASSOC);}
    public function count(ScannerCatalogFilter$f):int{[$where,$p]=$this->criteria($f);return(int)$this->stmt('SELECT COUNT(*) FROM scanners s'.$where,$p)->fetchColumn();}
    public function identities():array{return$this->stmt('SELECT id,codigo,imei,iccid,telefono FROM scanners')->fetchAll(\PDO::FETCH_ASSOC);}
}
