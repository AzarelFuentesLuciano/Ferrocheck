<?php

namespace App\Repositories\ControlEscaneres;

use App\Core\Database;
use PDO;

class ControlEscaneresRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::getConnection();
    }

    public function existeTablaScanners(): bool
    {
        $sql = 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tabla LIMIT 1';
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':tabla' => 'scanners']);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function obtenerResumenCatalogo(): array
    {
        if (!$this->existeTablaScanners()) {
            return [
                'total' => 0,
                'activos' => 0,
                'disponibles' => 0,
                'en_operacion' => 0,
            ];
        }

        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN UPPER(COALESCE(estado, '')) = 'DISPONIBLE' THEN 1 ELSE 0 END) AS disponibles,
                SUM(CASE WHEN UPPER(COALESCE(actividad, '')) IN ('ACTIVO', 'OPERANDO', 'EN OPERACION') THEN 1 ELSE 0 END) AS en_operacion
            FROM scanners
        ";

        $stmt = $this->conexion->query($sql);
        $fila = $stmt->fetch();

        if (!is_array($fila)) {
            return [
                'total' => 0,
                'activos' => 0,
                'disponibles' => 0,
                'en_operacion' => 0,
            ];
        }

        return [
            'total' => (int) ($fila['total'] ?? 0),
            'activos' => (int) ($fila['activos'] ?? 0),
            'disponibles' => (int) ($fila['disponibles'] ?? 0),
            'en_operacion' => (int) ($fila['en_operacion'] ?? 0),
        ];
    }

    public function obtenerCatalogo(int $limite = 250): array
    {
        if (!$this->existeTablaScanners()) {
            return [];
        }

        $sql = '
            SELECT
                id,
                codigo_interno,
                tag,
                marca,
                modelo,
                numero_serie,
                imei,
                chip,
                numero_telefonico,
                red,
                plan,
                pin,
                puk,
                actividad,
                area,
                ubicacion,
                estado,
                indice_conservacion,
                activo,
                observaciones,
                fecha_alta,
                fecha_actualizacion
            FROM scanners
            ORDER BY codigo_interno ASC
            LIMIT :limite
        ';

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function generarSiguienteCodigoInterno(): string
    {
        $sql = "
            SELECT COALESCE(MAX(CAST(SUBSTRING(codigo_interno, 5) AS UNSIGNED)), 0) AS ultimo
            FROM scanners
            WHERE codigo_interno REGEXP '^ESC-[0-9]{4,}$'
        ";

        $stmt = $this->conexion->query($sql);
        $ultimo = (int) $stmt->fetchColumn();
        $siguiente = $ultimo + 1;

        return 'ESC-' . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }

    public function crearScanner(array $payload): array
    {
        $codigoInterno = trim((string) ($payload['codigo_interno'] ?? ''));
        if ($codigoInterno === '') {
            $codigoInterno = $this->generarSiguienteCodigoInterno();
        }

        $sql = '
            INSERT INTO scanners (
                codigo_interno,
                tag,
                marca,
                modelo,
                numero_serie,
                imei,
                chip,
                numero_telefonico,
                red,
                plan,
                pin,
                puk,
                actividad,
                area,
                ubicacion,
                estado,
                indice_conservacion,
                activo,
                observaciones,
                fecha_alta,
                fecha_actualizacion
            ) VALUES (
                :codigo_interno,
                :tag,
                :marca,
                :modelo,
                :numero_serie,
                :imei,
                :chip,
                :numero_telefonico,
                :red,
                :plan,
                :pin,
                :puk,
                :actividad,
                :area,
                :ubicacion,
                :estado,
                :indice_conservacion,
                :activo,
                :observaciones,
                :fecha_alta,
                :fecha_actualizacion
            )
        ';

        $stmt = $this->conexion->prepare($sql);
        $ahora = date('Y-m-d H:i:s');
        $stmt->execute([
            ':codigo_interno' => $codigoInterno,
            ':tag' => $this->limpiar($payload['tag'] ?? null),
            ':marca' => $this->limpiar($payload['marca'] ?? null),
            ':modelo' => $this->limpiar($payload['modelo'] ?? null),
            ':numero_serie' => $this->limpiar($payload['numero_serie'] ?? null),
            ':imei' => $this->limpiar($payload['imei'] ?? null),
            ':chip' => $this->limpiar($payload['chip'] ?? null),
            ':numero_telefonico' => $this->limpiar($payload['numero_telefonico'] ?? null),
            ':red' => $this->limpiar($payload['red'] ?? null),
            ':plan' => $this->limpiar($payload['plan'] ?? null),
            ':pin' => $this->limpiar($payload['pin'] ?? null),
            ':puk' => $this->limpiar($payload['puk'] ?? null),
            ':actividad' => $this->limpiar($payload['actividad'] ?? null),
            ':area' => $this->limpiar($payload['area'] ?? null),
            ':ubicacion' => $this->limpiar($payload['ubicacion'] ?? null),
            ':estado' => $this->limpiar($payload['estado'] ?? null),
            ':indice_conservacion' => $this->numeroNullable($payload['indice_conservacion'] ?? null),
            ':activo' => $this->boolToInt($payload['activo'] ?? true),
            ':observaciones' => $this->limpiar($payload['observaciones'] ?? null),
            ':fecha_alta' => $ahora,
            ':fecha_actualizacion' => $ahora,
        ]);

        return [
            'id' => (int) $this->conexion->lastInsertId(),
            'codigo_interno' => $codigoInterno,
        ];
    }

    public function buscarExistenteParaImportacion(?string $codigoInterno, ?string $tag, ?string $numeroSerie): ?array
    {
        if ($codigoInterno !== null && $codigoInterno !== '') {
            $stmt = $this->conexion->prepare('SELECT id, codigo_interno FROM scanners WHERE codigo_interno = :codigo LIMIT 1');
            $stmt->execute([':codigo' => $codigoInterno]);
            $fila = $stmt->fetch();
            if (is_array($fila)) {
                return $fila;
            }
        }

        if ($tag !== null && $tag !== '') {
            $stmt = $this->conexion->prepare('SELECT id, codigo_interno FROM scanners WHERE tag = :tag LIMIT 1');
            $stmt->execute([':tag' => $tag]);
            $fila = $stmt->fetch();
            if (is_array($fila)) {
                return $fila;
            }
        }

        if ($numeroSerie !== null && $numeroSerie !== '') {
            $stmt = $this->conexion->prepare('SELECT id, codigo_interno FROM scanners WHERE numero_serie = :numero_serie LIMIT 1');
            $stmt->execute([':numero_serie' => $numeroSerie]);
            $fila = $stmt->fetch();
            if (is_array($fila)) {
                return $fila;
            }
        }

        return null;
    }

    public function actualizarDatosTecnicosImportacion(int $id, array $payload): void
    {
        $sql = '
            UPDATE scanners SET
                tag = :tag,
                marca = :marca,
                modelo = :modelo,
                numero_serie = :numero_serie,
                imei = :imei,
                chip = :chip,
                numero_telefonico = :numero_telefonico,
                red = :red,
                plan = :plan,
                pin = :pin,
                puk = :puk,
                fecha_actualizacion = :fecha_actualizacion
            WHERE id = :id
        ';

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':tag' => $this->limpiar($payload['tag'] ?? null),
            ':marca' => $this->limpiar($payload['marca'] ?? null),
            ':modelo' => $this->limpiar($payload['modelo'] ?? null),
            ':numero_serie' => $this->limpiar($payload['numero_serie'] ?? null),
            ':imei' => $this->limpiar($payload['imei'] ?? null),
            ':chip' => $this->limpiar($payload['chip'] ?? null),
            ':numero_telefonico' => $this->limpiar($payload['numero_telefonico'] ?? null),
            ':red' => $this->limpiar($payload['red'] ?? null),
            ':plan' => $this->limpiar($payload['plan'] ?? null),
            ':pin' => $this->limpiar($payload['pin'] ?? null),
            ':puk' => $this->limpiar($payload['puk'] ?? null),
            ':fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);
    }

    public function iniciarTransaccion(): void
    {
        if (!$this->conexion->inTransaction()) {
            $this->conexion->beginTransaction();
        }
    }

    public function confirmarTransaccion(): void
    {
        if ($this->conexion->inTransaction()) {
            $this->conexion->commit();
        }
    }

    public function revertirTransaccion(): void
    {
        if ($this->conexion->inTransaction()) {
            $this->conexion->rollBack();
        }
    }

    private function limpiar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
    }

    private function numeroNullable(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }

    private function boolToInt(mixed $valor): int
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        $texto = strtoupper(trim((string) $valor));
        if (in_array($texto, ['0', 'NO', 'FALSE', 'INACTIVO'], true)) {
            return 0;
        }

        return 1;
    }
}
