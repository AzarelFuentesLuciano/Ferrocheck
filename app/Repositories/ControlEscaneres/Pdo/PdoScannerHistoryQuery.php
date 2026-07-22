<?php
declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Pdo;

use App\Domain\ControlEscaneres\Scanner;
use App\Repositories\ControlEscaneres\Contracts\ScannerHistoryQueryInterface;

final class PdoScannerHistoryQuery extends AbstractPdoRepository implements ScannerHistoryQueryInterface
{
    public function getScannerSummary(int $id): ?Scanner
    {
        return (new PdoScannerRepository($this->pdo))->findById($id);
    }

    public function getScannerDetails(int $id): ?array
    {
        $row = $this->stmt('SELECT s.id,s.codigo,s.codigo_qr,s.tag_original,s.numero_serie,s.imei,s.marca,s.modelo,s.telefono,s.iccid,s.red,s.plan,s.actividad_habitual,s.area_habitual,s.ubicacion,s.antiguedad_descriptiva,s.observaciones,s.fotografia_principal,s.area_id,s.area_organizacional_id,ao.nombre area_organizacional_nombre,s.estado,s.indice_conservacion,s.activo,s.created_at,s.updated_at FROM scanners s LEFT JOIN areas_organizacionales ao ON ao.id=s.area_organizacional_id WHERE s.id=:id', ['id' => $id])->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function listMovements(int $id): array { return (new PdoScannerMovementRepository($this->pdo))->listByScannerId($id); }
    public function listInspections(int $id): array { return (new PdoScannerInspectionRepository($this->pdo))->listByScannerId($id); }
    public function listIncidents(int $id): array { return (new PdoScannerIncidentRepository($this->pdo))->listByScannerId($id); }
    public function listEvidences(int $id): array { return (new PdoEvidenceRepository($this->pdo))->listByScannerId($id); }
    public function listDifferences(int $id): array { return $this->stmt('SELECT d.* FROM scanner_inspeccion_diferencias d JOIN scanner_movimientos m ON m.id=d.movimiento_id WHERE m.scanner_id=:id ORDER BY d.created_at DESC,d.id DESC',['id'=>$id])->fetchAll(\PDO::FETCH_ASSOC); }
    public function listMaintenance(int $id): array { return (new PdoScannerMaintenanceRepository($this->pdo))->listByScannerId($id); }
    public function listIncidentFollowUps(int $id): array { return $this->stmt('SELECT f.*,i.tipo FROM scanner_incidencia_seguimientos f JOIN scanner_incidencias i ON i.id=f.incidencia_id WHERE i.scanner_id=:id ORDER BY f.created_at DESC,f.id DESC',['id'=>$id])->fetchAll(\PDO::FETCH_ASSOC); }

    public function listInspectionDetails(int $id): array
    {
        $repository = new PdoScannerInspectionRepository($this->pdo);
        $details = [];
        foreach ($this->listInspections($id) as $inspection) {
            $details[$inspection->id] = $repository->listDetailsByInspectionId($inspection->id);
        }
        return $details;
    }

    public function buildTimeline(int $id): array
    {
        $rows = [];
        foreach ($this->listMovements($id) as $movement) $rows[] = ['type' => 'movimiento_' . $movement->status->value, 'at' => $movement->entregadoAt, 'entity' => $movement];
        foreach ($this->listInspections($id) as $inspection) $rows[] = ['type' => 'inspeccion_' . $inspection->type->value, 'at' => $inspection->inspectedAt, 'entity' => $inspection];
        foreach ($this->listIncidents($id) as $incident) $rows[] = ['type' => 'incidencia_' . $incident->status->value, 'at' => $incident->reportedAt, 'entity' => $incident];
        foreach ($this->stmt('SELECT estado_anterior,estado_nuevo,motivo,changed_at FROM scanner_estado_historial WHERE scanner_id=:id', ['id' => $id])->fetchAll(\PDO::FETCH_ASSOC) as $state) {
            $rows[] = ['type' => 'estado_' . $state['estado_nuevo'], 'at' => new \DateTimeImmutable($state['changed_at']), 'entity' => $state];
        }
        foreach ($this->stmt('SELECT estado,created_at FROM scanner_mantenimientos WHERE scanner_id=:id', ['id' => $id])->fetchAll(\PDO::FETCH_ASSOC) as $maintenance) {
            $rows[] = ['type' => 'mantenimiento_' . $maintenance['estado'], 'at' => new \DateTimeImmutable($maintenance['created_at']), 'entity' => $maintenance];
        }
        usort($rows, static fn(array $left, array $right): int => $right['at'] <=> $left['at']);
        return $rows;
    }
}
