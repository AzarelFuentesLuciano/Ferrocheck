<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class InventarioRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? $this->conectar();
    }

    public function conectar(): PDO
    {
        return Database::getConnection();
    }

    private function obtenerColumnasInternas(): array
    {
        $catalogoPath = __DIR__ . '/../../config/catalogo_columnas.php';

        if (!is_file($catalogoPath)) {
            throw new \RuntimeException('No se encontró el catálogo de columnas del inventario.');
        }

        $catalogo = require $catalogoPath;

        if (!is_array($catalogo)) {
            throw new \RuntimeException('El catálogo de columnas del inventario es inválido.');
        }

        $columnas = [];

        foreach ($catalogo as $entrada) {
            if (!is_array($entrada)) {
                continue;
            }

            $interno = trim((string) ($entrada['internal'] ?? ''));

            if ($interno === '') {
                continue;
            }

            if (!in_array($interno, $columnas, true)) {
                $columnas[] = $interno;
            }
        }

        return $columnas;
    }

    private function normalizarRegistroParaInsercion(array $registro, array $columnas): array
    {
        $valores = [];

        foreach ($columnas as $columna) {
            $valor = $registro[$columna] ?? null;
            $valores[':' . $columna] = $valor === null ? null : (string) $valor;
        }

        return $valores;
    }

    public function truncar(): void
    {
        $this->conexion->exec('DELETE FROM inventario');
    }

    public function insertarRegistros(array $registros): int
    {
        if (empty($registros)) {
            return 0;
        }

        $columnas = $this->obtenerColumnasInternas();
        if (empty($columnas)) {
            throw new \RuntimeException('No existen columnas internas disponibles para la importación.');
        }

        $listaColumnas = implode(', ', $columnas);
        $placeholders = implode(', ', array_map(static fn (string $columna): string => ':' . $columna, $columnas));
        $sql = 'INSERT INTO inventario (' . $listaColumnas . ') VALUES (' . $placeholders . ')';

        $this->conexion->beginTransaction();

        try {
            $this->conexion->exec('DELETE FROM inventario');

            $stmt = $this->conexion->prepare($sql);

            foreach ($registros as $registro) {
                $stmt->execute($this->normalizarRegistroParaInsercion((array) $registro, $columnas));
            }

            $this->conexion->commit();
            return count($registros);
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    public function buscarEquipos(array $equipos): array
    {
        if (empty($equipos)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($equipos), '?'));
        $sql = 'SELECT * FROM inventario WHERE UPPER(equipo) IN (' . $placeholders . ')';
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute(array_map(static fn (string $equipo): string => strtoupper($equipo), $equipos));

        return $stmt->fetchAll();
    }

    public function buscarEquipoPorCodigo(string $codigo): ?array
    {
        $codigoNormalizado = strtoupper(trim($codigo));

        if ($codigoNormalizado === '') {
            return null;
        }

        $sql = 'SELECT * FROM inventario WHERE UPPER(equipo) = ? LIMIT 1';
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$codigoNormalizado]);
        $registro = $stmt->fetch();

        return $registro === false ? null : $registro;
    }

    public function contarInventario(): int
    {
        $stmt = $this->conexion->query('SELECT COUNT(*) FROM inventario');
        return (int) $stmt->fetchColumn();
    }

    public function obtenerResumenDashboard(): array
    {
        $ubicacionExpr = "UPPER(COALESCE(NULLIF(estacion, ''), NULLIF(estacion_de_ultimo_movimiento, ''), NULLIF(estacion_de_destino, ''), NULLIF(estacion_de_origen, ''), ''))";

        $sql = "
            SELECT
                COUNT(*) AS inventario_ferromex,
                SUM(CASE WHEN {$ubicacionExpr} LIKE '%ENCANTADA%' THEN 1 ELSE 0 END) AS en_encantada,
                SUM(CASE WHEN {$ubicacionExpr} NOT LIKE '%ENCANTADA%' THEN 1 ELSE 0 END) AS otra_ubicacion,
                MAX(fecha_importacion) AS ultima_actualizacion
            FROM inventario
        ";

        $stmt = $this->conexion->query($sql);
        $resultado = $stmt->fetch();

        return $resultado === false ? [] : $resultado;
    }
}