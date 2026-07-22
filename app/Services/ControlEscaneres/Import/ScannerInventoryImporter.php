<?php
declare(strict_types=1);

namespace App\Services\ControlEscaneres\Import;

final class ScannerInventoryImporter
{
    public function __construct(private \PDO $pdo) {}

    public function import(array $preview, string $baseUrl, int $actorId): array
    {
        $stats = $preview['stats'] ?? [];
        $candidates = $preview['candidates'] ?? [];
        if (!is_array($candidates) || (int) ($stats['nuevos'] ?? -1) !== count($candidates)) {
            throw new \RuntimeException('El dry-run no coincide con los candidatos; se canceló la importación.');
        }
        foreach ($candidates as $record) {
            if (($record['result'] ?? null) !== 'nuevo' || empty($record['tag']) || empty($record['code'])) {
                throw new \RuntimeException('El dry-run contiene un registro no importable.');
            }
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO scanners(codigo,codigo_qr,tag_original,marca,modelo,numero_serie,imei,telefono,iccid,red,plan,actividad_habitual,area_habitual,ubicacion,antiguedad_descriptiva,observaciones,estado,activo,created_by,updated_by) '
            . 'VALUES(:code,:qr,:tag,:brand,:model,NULL,:imei,:phone,:iccid,:network,:plan,:activity,:area,:location,:age,:observations,:status,1,:actor,:actor)'
        );
        $updateQr = $this->pdo->prepare('UPDATE scanners SET codigo_qr=:qr WHERE id=:id');
        $codes = [];
        $available = 0;
        $repair = 0;

        $this->pdo->beginTransaction();
        try {
            foreach ($candidates as $record) {
                $insert->execute([
                    'code' => $record['code'],
                    'qr' => 'scanner:' . $record['code'],
                    'tag' => $record['tag'],
                    'brand' => $record['brand'] ?: 'Por definir',
                    'model' => $record['model'] ?: 'Por definir',
                    'imei' => $record['imei'],
                    'phone' => $record['phone'],
                    'iccid' => $record['iccid'],
                    'network' => $record['network'],
                    'plan' => $record['plan'],
                    'activity' => $record['activity'],
                    'area' => $record['area'],
                    'location' => $record['location'],
                    'age' => $record['age'],
                    'observations' => $record['observations'],
                    'status' => $record['status'],
                    'actor' => $actorId,
                ]);
                $id = (int) $this->pdo->lastInsertId();
                $qr = rtrim($baseUrl, '/') . '/index.php?modulo=control-escaneres&seccion=expediente&scanner_id=' . $id;
                $updateQr->execute(['qr' => $qr, 'id' => $id]);
                $codes[] = $record['code'];
                $record['status'] === 'pendiente_reparacion' ? $repair++ : $available++;
            }
            $audit = $this->pdo->prepare("INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_nuevo_json,metadata_json,created_at) VALUES(:actor,'importar_inventario','control-escaneres','scanner_import',NULL,'exito',:new_values,:metadata,CURRENT_TIMESTAMP(6))");
            $audit->execute([
                'actor' => $actorId,
                'new_values' => json_encode(['codes' => $codes], JSON_THROW_ON_ERROR),
                'metadata' => json_encode([
                    'inserted' => count($codes),
                    'excluded_conflicts' => (int) ($stats['conflictos'] ?? 0),
                    'invalid' => (int) ($stats['invalidos'] ?? 0),
                    'pin_puk_imported' => false,
                    'modem_imported' => false,
                ], JSON_THROW_ON_ERROR),
            ]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }

        return [
            'inserted' => count($codes),
            'codes' => $codes,
            'available' => $available,
            'pending_repair' => $repair,
            'conflicts_excluded' => (int) ($stats['conflictos'] ?? 0),
            'invalid_excluded' => (int) ($stats['invalidos'] ?? 0),
        ];
    }
}
