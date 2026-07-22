<?php
declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Pdo;

use App\Domain\ControlEscaneres\{MovementStatus, ScannerFolio, ScannerMovement};
use App\DTO\ControlEscaneres\ScannerMovementCreateData;
use App\Repositories\ControlEscaneres\Contracts\ScannerMovementRepositoryInterface;

final class PdoScannerMovementRepository extends AbstractPdoRepository implements ScannerMovementRepositoryInterface
{
    private function map(array $row): ScannerMovement
    {
        return new ScannerMovement(
            (int) $row['id'], (int) $row['scanner_id'], new ScannerFolio($row['folio']), new MovementStatus($row['estado']),
            $row['persona_entrega_nombre'], $row['numero_empleado'], $row['area_id'] === null ? null : (int) $row['area_id'], $row['turno'],
            $this->date($row['entregado_at']), $this->date($row['recibido_at']), $this->date($row['vence_at']),
            $row['entrega_registrada_por'] === null ? null : (int) $row['entrega_registrada_por'],
            $row['recepcion_registrada_por'] === null ? null : (int) $row['recepcion_registrada_por'],
            $row['devolucion_recibida_por_nombre'], $row['duracion_segundos'] === null ? null : (int) $row['duracion_segundos'],
            $row['observaciones'], $row['cancelado_por'] === null ? null : (int) $row['cancelado_por'], $this->date($row['cancelado_at']),
            $row['motivo_cancelacion'], $this->date($row['created_at']), $this->date($row['updated_at']),
            $row['area_nombre'] ?? null, $row['supervisor_nombre'] ?? null, $row['responsable_entrega_nombre'] ?? null,
            $row['responsable_recepcion_nombre'] ?? null,
        );
    }

    private function one(string $query, array $params): ?ScannerMovement { $row = $this->stmt($query, $params)->fetch(\PDO::FETCH_ASSOC); return is_array($row) ? $this->map($row) : null; }
    private function many(string $query, array $params = []): array { return array_map(fn(array $row) => $this->map($row), $this->stmt($query, $params)->fetchAll(\PDO::FETCH_ASSOC)); }
    public function findById(int $id): ?ScannerMovement { return $this->one('SELECT * FROM scanner_movimientos WHERE id=:id', ['id' => $id]); }
    public function findOpenByScannerId(int $id): ?ScannerMovement { return $this->one("SELECT * FROM scanner_movimientos WHERE scanner_id=:id AND estado='abierto'", ['id' => $id]); }
    public function hasOpenMovement(int $id): bool { return $this->findOpenByScannerId($id) !== null; }

    public function create(ScannerMovementCreateData $data): ScannerMovement
    {
        $areaId = null;
        if ($data->areaName !== null) {
            $areaSql = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? 'INSERT OR IGNORE INTO scanner_areas(nombre,activo) VALUES(:name,1)'
                : 'INSERT INTO scanner_areas(nombre,activo) VALUES(:name,1) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)';
            $this->stmt($areaSql, ['name' => $data->areaName]);
            $areaId = (int) $this->stmt('SELECT id FROM scanner_areas WHERE nombre=:name', ['name' => $data->areaName])->fetchColumn();
        }
        $this->stmt("INSERT INTO scanner_movimientos(scanner_id,folio,estado,persona_entrega_nombre,supervisor_nombre,responsable_entrega_nombre,numero_empleado,area_id,area_nombre,turno,entregado_at,entrega_registrada_por,observaciones) VALUES(:scanner,:folio,'abierto',:person,:supervisor,:responsible,:employee,:area_id,:area_name,:shift,:delivered,:actor,:observations)", [
            'scanner' => $data->scannerId, 'folio' => $data->folio->value, 'person' => $data->personName,
            'supervisor' => $data->supervisorName, 'responsible' => $data->responsibleName, 'employee' => $data->employeeNumber,
            'area_id' => $areaId ?: null, 'area_name' => $data->areaName, 'shift' => $data->shift,
            'delivered' => $data->deliveredAt->format('Y-m-d H:i:s.u'), 'actor' => $data->actor->userId, 'observations' => $data->observations,
        ]);
        return $this->findById((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('No fue posible recuperar el movimiento creado.');
    }

    public function closeAsReturned(int $id, \DateTimeImmutable $at, int $actor, string $receiver, int $durationSeconds, ?string $responsibleName = null): void
    {
        $this->stmt("UPDATE scanner_movimientos SET estado='devuelto',recibido_at=:at,recepcion_registrada_por=:actor,devolucion_recibida_por_nombre=:receiver,responsable_recepcion_nombre=:responsible,duracion_segundos=:duration WHERE id=:id AND estado='abierto'", [
            'id' => $id, 'at' => $at->format('Y-m-d H:i:s.u'), 'actor' => $actor, 'receiver' => $receiver,
            'responsible' => $responsibleName, 'duration' => $durationSeconds,
        ]);
    }
    public function cancel(int $id, int $actor, string $reason): void { $this->stmt("UPDATE scanner_movimientos SET estado='cancelado',cancelado_por=:actor,motivo_cancelacion=:reason,cancelado_at=CURRENT_TIMESTAMP WHERE id=:id", compact('id', 'actor', 'reason')); }
    public function markOverdue(\DateTimeImmutable $at): int { $statement = $this->stmt("UPDATE scanner_movimientos SET estado='vencido' WHERE estado='abierto' AND vence_at<:at", ['at' => $at->format('Y-m-d H:i:s.u')]); return $statement->rowCount(); }
    public function listByScannerId(int $id): array { return $this->many('SELECT * FROM scanner_movimientos WHERE scanner_id=:id ORDER BY entregado_at DESC', ['id' => $id]); }
    public function listOpen(): array { return $this->many("SELECT * FROM scanner_movimientos WHERE estado='abierto'"); }
    public function listByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array { return $this->many('SELECT * FROM scanner_movimientos WHERE entregado_at BETWEEN :from AND :to', ['from' => $from->format('Y-m-d H:i:s.u'), 'to' => $to->format('Y-m-d H:i:s.u')]); }
    public function listByActor(int $actorId): array { return $this->many('SELECT * FROM scanner_movimientos WHERE entrega_registrada_por=:id OR recepcion_registrada_por=:id', ['id' => $actorId]); }
    public function listByArea(int $areaId): array { return $this->many('SELECT * FROM scanner_movimientos WHERE area_id=:id', ['id' => $areaId]); }
    public function countOpen(): int { return (int) $this->stmt("SELECT COUNT(*) FROM scanner_movimientos WHERE estado='abierto'")->fetchColumn(); }
    public function countOverdue(\DateTimeImmutable $at): int { return (int) $this->stmt("SELECT COUNT(*) FROM scanner_movimientos WHERE estado='abierto' AND vence_at<:at", ['at' => $at->format('Y-m-d H:i:s.u')])->fetchColumn(); }
    public function lockOpenMovementForUpdate(int $id): ?ScannerMovement { return $this->one("SELECT * FROM scanner_movimientos WHERE scanner_id=:id AND estado='abierto'" . $this->forUpdate(), ['id' => $id]); }
}
