<?php
declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Pdo;

final class PdoScannerMaintenanceRepository extends AbstractPdoRepository
{
    public function open(int $scannerId, array $data, int $actorId, \DateTimeImmutable $at): int
    {
        if ($this->findOpen($scannerId) !== null) throw new \DomainException('Ya existe un mantenimiento abierto para este equipo.');
        $this->stmt("INSERT INTO scanner_mantenimientos(scanner_id,estado,motivo,tecnico_proveedor,diagnostico,costo,iniciado_at,fecha_estimada,registrada_por) VALUES(:scanner,'abierto',:reason,:technician,:diagnosis,:cost,:started,:estimated,:actor)", [
            'scanner' => $scannerId, 'reason' => $data['reason'], 'technician' => $data['technician'],
            'diagnosis' => $data['diagnosis'], 'cost' => $data['cost'], 'started' => $at->format('Y-m-d H:i:s.u'),
            'estimated' => $data['estimated'], 'actor' => $actorId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function close(int $scannerId, string $result, string $finalStatus, int $actorId, \DateTimeImmutable $at): int
    {
        $open = $this->findOpen($scannerId) ?? throw new \DomainException('No existe un mantenimiento abierto para este equipo.');
        $this->stmt("UPDATE scanner_mantenimientos SET estado='cerrado',resultado=:result,estado_final=:status,finalizado_at=:finished,updated_at=CURRENT_TIMESTAMP(6) WHERE id=:id AND estado='abierto'", [
            'result' => $result, 'status' => $finalStatus, 'finished' => $at->format('Y-m-d H:i:s.u'), 'id' => $open['id'],
        ]);
        return (int) $open['id'];
    }

    public function findOpen(int $scannerId): ?array
    {
        $row = $this->stmt("SELECT * FROM scanner_mantenimientos WHERE scanner_id=:scanner AND estado='abierto' ORDER BY id DESC LIMIT 1" . $this->forUpdate(), ['scanner' => $scannerId])->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function listByScannerId(int $scannerId): array
    {
        return $this->stmt('SELECT * FROM scanner_mantenimientos WHERE scanner_id=:scanner ORDER BY iniciado_at DESC', ['scanner' => $scannerId])->fetchAll(\PDO::FETCH_ASSOC);
    }
}
