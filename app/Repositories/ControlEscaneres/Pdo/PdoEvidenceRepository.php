<?php
declare(strict_types=1);

namespace App\Repositories\ControlEscaneres\Pdo;

use App\DTO\ControlEscaneres\{AuthenticatedActorData, ScannerEvidenceMetadata};
use App\Repositories\ControlEscaneres\Contracts\EvidenceRepositoryInterface;

final class PdoEvidenceRepository extends AbstractPdoRepository implements EvidenceRepositoryInterface
{
    public function create(ScannerEvidenceMetadata $data): int
    {
        $this->stmt('INSERT INTO scanner_evidencias(scanner_id,movimiento_id,inspeccion_id,incidencia_id,mantenimiento_id,tipo,ruta_storage,mime_type,tamano_bytes,hash_sha256,capturada_at,registrada_por,activo) VALUES(:s,:m,:n,:i,:maintenance,:t,:r,:y,:b,:h,:a,:u,1)', [
            's' => $data->scannerId, 'm' => $data->movementId, 'n' => $data->inspectionId,
            'i' => $data->incidentId, 'maintenance' => $data->maintenanceId, 't' => $data->type, 'r' => $data->storagePath,
            'y' => $data->mimeType, 'b' => $data->sizeBytes, 'h' => $data->sha256,
            'a' => $data->capturedAt->format('Y-m-d H:i:s.u'), 'u' => $data->actor->userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function map(array $row): ScannerEvidenceMetadata
    {
        return new ScannerEvidenceMetadata(
            $row['scanner_id'] === null ? null : (int) $row['scanner_id'],
            $row['tipo'], $row['ruta_storage'], $row['mime_type'], (int) $row['tamano_bytes'],
            $row['hash_sha256'], $this->date($row['capturada_at']),
            new AuthenticatedActorData((int) $row['registrada_por'], 'persisted'),
            $row['movimiento_id'] === null ? null : (int) $row['movimiento_id'],
            $row['inspeccion_id'] === null ? null : (int) $row['inspeccion_id'],
            $row['incidencia_id'] === null ? null : (int) $row['incidencia_id'],
            (int) $row['id'],
            $row['mantenimiento_id'] === null ? null : (int) $row['mantenimiento_id'],
        );
    }

    private function many(string $query, int $id): array
    {
        return array_map(fn(array $row) => $this->map($row), $this->stmt($query, ['i' => $id])->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?ScannerEvidenceMetadata
    {
        $row = $this->stmt('SELECT * FROM scanner_evidencias WHERE id=:i AND activo=1', ['i' => $id])->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $this->map($row) : null;
    }
    public function listByScannerId(int $id): array { return $this->many('SELECT * FROM scanner_evidencias WHERE scanner_id=:i AND activo=1 ORDER BY capturada_at DESC', $id); }
    public function listByMovementId(int $id): array { return $this->many('SELECT * FROM scanner_evidencias WHERE movimiento_id=:i AND activo=1 ORDER BY capturada_at', $id); }
    public function listByInspectionId(int $id): array { return $this->many('SELECT * FROM scanner_evidencias WHERE inspeccion_id=:i AND activo=1 ORDER BY capturada_at', $id); }
    public function listByIncidentId(int $id): array { return $this->many('SELECT * FROM scanner_evidencias WHERE incidencia_id=:i AND activo=1 ORDER BY capturada_at', $id); }
    public function deactivate(int $id, int $actor): void { $this->stmt('UPDATE scanner_evidencias SET activo=0 WHERE id=:i', ['i' => $id]); }
}
