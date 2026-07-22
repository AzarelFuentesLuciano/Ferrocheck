<?php
declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Pdo;

use App\Domain\ControlEscaneres\{IncidentSeverity, IncidentStatus, ScannerIncident};
use App\DTO\ControlEscaneres\ScannerIncidentCreateData;
use App\Repositories\ControlEscaneres\Contracts\ScannerIncidentRepositoryInterface;

final class PdoScannerIncidentRepository extends AbstractPdoRepository implements ScannerIncidentRepositoryInterface
{
    private const COLUMNS = 'id, scanner_id, movimiento_id, tipo, severidad, descripcion, estado, '
        .'reportado_por_nombre, registrada_por, reportada_at, resolucion, resuelta_por, resuelta_at, '
        .'created_at, updated_at';

    private function map(array $row): ScannerIncident
    {
        return new ScannerIncident(
            (int) $row['id'],
            (int) $row['scanner_id'],
            $row['movimiento_id'] === null ? null : (int) $row['movimiento_id'],
            $row['tipo'],
            new IncidentSeverity($row['severidad']),
            $row['descripcion'],
            new IncidentStatus($row['estado']),
            $row['reportado_por_nombre'],
            $row['registrada_por'] === null ? null : (int) $row['registrada_por'],
            $this->date($row['reportada_at']),
            $row['resolucion'],
            $row['resuelta_por'] === null ? null : (int) $row['resuelta_por'],
            $this->date($row['resuelta_at']),
            $this->date($row['created_at']),
            $this->date($row['updated_at']),
        );
    }

    private function many(string $query, array $parameters = []): array
    {
        return array_map(
            fn (array $row): ScannerIncident => $this->map($row),
            $this->stmt($query, $parameters)->fetchAll(\PDO::FETCH_ASSOC),
        );
    }

    public function create(ScannerIncidentCreateData $data): ScannerIncident
    {
        $this->stmt(
            "INSERT INTO scanner_incidencias
                (scanner_id, movimiento_id, tipo, severidad, descripcion, estado, reportado_por_nombre,
                 registrada_por, reportada_at, resolucion, resuelta_por, resuelta_at)
             VALUES
                (:scanner_id, :movimiento_id, :tipo, :severidad, :descripcion, 'abierta', NULL,
                 :registrada_por, :reportada_at, NULL, NULL, NULL)",
            [
                'scanner_id' => $data->scannerId,
                'movimiento_id' => $data->movementId,
                'tipo' => $data->type,
                'severidad' => $data->severity->value,
                'descripcion' => $data->description,
                'registrada_por' => $data->actor->userId,
                'reportada_at' => $data->reportedAt->format('Y-m-d H:i:s.u'),
            ],
        );

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?ScannerIncident
    {
        $row = $this->stmt(
            'SELECT '.self::COLUMNS.' FROM scanner_incidencias WHERE id = :id',
            ['id' => $id],
        )->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $this->map($row) : null;
    }

    public function listByScannerId(int $id): array
    {
        return $this->many(
            'SELECT '.self::COLUMNS.' FROM scanner_incidencias WHERE scanner_id = :id',
            ['id' => $id],
        );
    }

    public function listByMovementId(int $id): array
    {
        return $this->many(
            'SELECT '.self::COLUMNS.' FROM scanner_incidencias WHERE movimiento_id = :id',
            ['id' => $id],
        );
    }

    public function listByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->many(
            'SELECT '.self::COLUMNS.' FROM scanner_incidencias WHERE reportada_at BETWEEN :from AND :to',
            ['from' => $from->format('Y-m-d H:i:s.u'), 'to' => $to->format('Y-m-d H:i:s.u')],
        );
    }

    public function listOpen(): array
    {
        return $this->many(
            "SELECT ".self::COLUMNS." FROM scanner_incidencias WHERE estado NOT IN ('resuelta', 'cancelada')",
        );
    }

    public function changeStatus(int $id, IncidentStatus $status, int $actor): void
    {
        $this->stmt('UPDATE scanner_incidencias SET estado = :status WHERE id = :id', ['status' => $status->value, 'id' => $id]);
    }

    public function changeSeverity(int $id, IncidentSeverity $severity, int $actor): void
    {
        $this->stmt('UPDATE scanner_incidencias SET severidad = :severity WHERE id = :id', ['severity' => $severity->value, 'id' => $id]);
    }

    public function addFollowUp(int $id, ?string $previousStatus, string $newStatus, string $comment, int $actor): void
    {
        $this->stmt(
            'INSERT INTO scanner_incidencia_seguimientos(incidencia_id,estado_anterior,estado_nuevo,comentario,registrado_por) VALUES(:incident,:previous,:new_status,:comment,:actor)',
            ['incident' => $id, 'previous' => $previousStatus, 'new_status' => $newStatus, 'comment' => $comment, 'actor' => $actor],
        );
    }

    public function listFollowUps(int $id): array
    {
        return $this->stmt('SELECT estado_anterior,estado_nuevo,comentario,registrado_por,created_at FROM scanner_incidencia_seguimientos WHERE incidencia_id=:id ORDER BY created_at DESC,id DESC', ['id' => $id])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resolve(int $id, string $resolution, int $actor, \DateTimeImmutable $at): void
    {
        $this->stmt(
            "UPDATE scanner_incidencias
             SET estado = 'resuelta', resolucion = :resolution, resuelta_por = :actor, resuelta_at = :resolved_at
             WHERE id = :id",
            ['resolution' => $resolution, 'actor' => $actor, 'resolved_at' => $at->format('Y-m-d H:i:s.u'), 'id' => $id],
        );
    }

    public function cancel(int $id, string $reason, int $actor, \DateTimeImmutable $at): void
    {
        $this->stmt(
            "UPDATE scanner_incidencias SET estado='cancelada',resolucion=:reason,resuelta_por=:actor,resuelta_at=:at WHERE id=:id",
            ['reason' => $reason, 'actor' => $actor, 'at' => $at->format('Y-m-d H:i:s.u'), 'id' => $id],
        );
    }

    public function countOpenBySeverity(IncidentSeverity $severity): int
    {
        return (int) $this->stmt(
            "SELECT COUNT(*) FROM scanner_incidencias WHERE severidad = :severity AND estado NOT IN ('resuelta', 'cancelada')",
            ['severity' => $severity->value],
        )->fetchColumn();
    }
}
