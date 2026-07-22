<?php
declare(strict_types=1);
namespace App\Repositories\ControlEscaneres\Pdo;
use App\DTO\ControlEscaneres\InspectionDifference;
final class PdoInspectionDifferenceRepository extends AbstractPdoRepository
{
    public function replaceForMovement(int$movementId,int$deliveryInspectionId,int$receptionInspectionId,array$differences):void{$this->stmt('DELETE FROM scanner_inspeccion_diferencias WHERE movimiento_id=:movement',['movement'=>$movementId]);foreach($differences as$difference){if(!$difference instanceof InspectionDifference)throw new\InvalidArgumentException('Diferencia de inspección inválida.');$this->stmt('INSERT INTO scanner_inspeccion_diferencias(movimiento_id,inspeccion_entrega_id,inspeccion_recepcion_id,componente,valor_anterior,valor_nuevo,clasificacion,requiere_revision) VALUES(:movement,:delivery,:reception,:component,:before,:after,:classification,:review)',['movement'=>$movementId,'delivery'=>$deliveryInspectionId,'reception'=>$receptionInspectionId,'component'=>$difference->component,'before'=>is_array($difference->before)?($difference->before['estado']??null):null,'after'=>is_array($difference->after)?($difference->after['estado']??null):null,'classification'=>$difference->classification,'review'=>(int)$difference->requiresReview]);}}
    public function listByMovementId(int$movementId):array{return$this->stmt('SELECT * FROM scanner_inspeccion_diferencias WHERE movimiento_id=:movement ORDER BY componente',['movement'=>$movementId])->fetchAll(\PDO::FETCH_ASSOC);}
}
