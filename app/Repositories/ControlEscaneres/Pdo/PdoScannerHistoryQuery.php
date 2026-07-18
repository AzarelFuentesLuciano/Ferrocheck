<?php
declare(strict_types=1);namespace App\Repositories\ControlEscaneres\Pdo;use App\Repositories\ControlEscaneres\Contracts\ScannerHistoryQueryInterface;use App\Domain\ControlEscaneres\Scanner;
final class PdoScannerHistoryQuery extends AbstractPdoRepository implements ScannerHistoryQueryInterface
{
    public function getScannerSummary(int$id):?Scanner{return(new PdoScannerRepository($this->pdo))->findById($id);}
    public function getScannerDetails(int$id):?array{$r=$this->stmt('SELECT id,codigo,codigo_qr,numero_serie,imei,marca,modelo,telefono,iccid,area_id,estado,indice_conservacion,activo,created_at,updated_at FROM scanners WHERE id=:id',['id'=>$id])->fetch(\PDO::FETCH_ASSOC);return is_array($r)?$r:null;}
    public function listMovements(int$id):array{return(new PdoScannerMovementRepository($this->pdo))->listByScannerId($id);}
    public function listInspections(int$id):array{return(new PdoScannerInspectionRepository($this->pdo))->listByScannerId($id);}
    public function listInspectionDetails(int$id):array{$out=[];foreach($this->listInspections($id)as$i)$out[$i->id]=(new PdoScannerInspectionRepository($this->pdo))->listDetailsByInspectionId($i->id);return$out;}
    public function listIncidents(int$id):array{return(new PdoScannerIncidentRepository($this->pdo))->listByScannerId($id);}
    public function listEvidences(int$id):array{return(new PdoEvidenceRepository($this->pdo))->listByScannerId($id);}
    public function buildTimeline(int$id):array{$rows=[];foreach($this->listMovements($id)as$m)$rows[]=['type'=>'movimiento','at'=>$m->entregadoAt,'entity'=>$m];foreach($this->listIncidents($id)as$i)$rows[]=['type'=>'incidencia','at'=>$i->reportedAt,'entity'=>$i];usort($rows,fn($a,$b)=>$b['at']<=>$a['at']);return$rows;}
}
