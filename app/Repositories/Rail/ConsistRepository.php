<?php

declare(strict_types=1);

namespace App\Repositories\Rail;

use PDO;
use RuntimeException;
use Throwable;

class ConsistRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createDraft(array $draft): array
    {
        return $this->transaction(function () use ($draft): array {
            $catalogId = $this->catalogVersion((array) $draft['catalog_version'], (int) $draft['created_by']);
            $folio = $this->nextFolio((int) gmdate('Y'));
            $statement = $this->pdo->prepare(
                'INSERT INTO rail_consists
                 (folio,analysis_token,fecha_inicio,fecha_fin,status,total_units,total_platforms,source_catalog_version_id,issues_json,created_by)
                 VALUES(:folio,:token,:start_date,:end_date,\'borrador\',:units,:platforms,:catalog,:issues,:actor)'
            );
            $statement->execute([
                'folio' => $folio,
                'token' => $draft['analysis_token'],
                'start_date' => $draft['fecha_inicio'],
                'end_date' => $draft['fecha_fin'],
                'units' => $draft['total_units'],
                'platforms' => $draft['total_platforms'],
                'catalog' => $catalogId,
                'issues' => $this->json($draft['issues'] ?? []),
                'actor' => $draft['created_by'],
            ]);
            $consistId = (int) $this->pdo->lastInsertId();

            $insertPlatform = $this->pdo->prepare(
                'INSERT INTO rail_consist_platforms(consist_id,platform_number,position,track,total_units)
                 VALUES(:consist,:number,:position,:track,:units)'
            );
            $platformIds = [];
            foreach ($draft['platforms'] as $platform) {
                $insertPlatform->execute([
                    'consist' => $consistId,
                    'number' => $platform['platform_number'],
                    'position' => $platform['position'],
                    'track' => $platform['track'] ?: null,
                    'units' => $platform['total_units'],
                ]);
                $platformIds[(int) $platform['position']] = (int) $this->pdo->lastInsertId();
            }

            $insertUnit = $this->pdo->prepare(
                'INSERT INTO rail_consist_units
                 (consist_id,platform_id,global_position,platform_position,vin,track,route_code,market,
                  shipping_destination,final_data_json,vehicle_load_data_json,shippers_data_json,
                  cnacs_selected_data_json,cnacs_additional_data_json,trace_json,issues_json)
                 VALUES
                 (:consist,:platform,:global,:within,:vin,:track,:route,:market,:destination,:final,
                  :vehicle,:shippers,:cnacs,:additional,:trace,:issues)'
            );
            foreach ($draft['units'] as $unit) {
                $platformPosition = intdiv((int) $unit['global_position'] - 1, 8) + 1;
                $source = (array) $unit['source_data'];
                $insertUnit->execute([
                    'consist' => $consistId,
                    'platform' => $platformIds[$platformPosition],
                    'global' => $unit['global_position'],
                    'within' => $unit['platform_position'],
                    'vin' => $unit['vin'],
                    'track' => $unit['track'] ?: null,
                    'route' => $unit['route_code'] ?: null,
                    'market' => $unit['market'] ?: null,
                    'destination' => $unit['shipping_destination'] ?: null,
                    'final' => $this->json($unit['final_columns']),
                    'vehicle' => $this->json($source['vehicle_load_report'] ?? []),
                    'shippers' => $this->json($source['shippers'] ?? []),
                    'cnacs' => $this->json($source['cnacs_selected'] ?? []),
                    'additional' => $this->json([
                        'count' => (int) ($source['cnacs_additional_count'] ?? 0),
                        'rows' => (array) ($source['cnacs_additional'] ?? []),
                    ]),
                    'trace' => $this->json($unit['trace']),
                    'issues' => $this->json($unit['issues']),
                ]);
            }
            $this->audit((int) $draft['created_by'], $consistId, $folio, (int) $draft['total_units']);
            return $this->find($consistId) ?? throw new RuntimeException('No fue posible recuperar el borrador.');
        });
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.*,u.nombre AS created_by_name,v.source_filename,v.source_sha256
             FROM rail_consists c JOIN usuarios u ON u.id=c.created_by
             JOIN rail_catalog_versions v ON v.id=c.source_catalog_version_id WHERE c.id=:id'
        );
        $statement->execute(['id' => $id]);
        $result = $statement->fetch();
        if (!is_array($result)) {
            return null;
        }
        $platforms = $this->pdo->prepare('SELECT * FROM rail_consist_platforms WHERE consist_id=:id ORDER BY position');
        $platforms->execute(['id' => $id]);
        $result['platforms'] = $platforms->fetchAll();
        $units = $this->pdo->prepare('SELECT * FROM rail_consist_units WHERE consist_id=:id ORDER BY global_position');
        $units->execute(['id' => $id]);
        $result['units'] = array_map([$this, 'hydrateUnit'], $units->fetchAll());
        $result['issues'] = $this->decode($result['issues_json'] ?? null);
        return $result;
    }

    public function history(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];
        foreach (['folio' => 'c.folio', 'estado' => 'c.status'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = "{$column} LIKE :{$key}";
                $params[$key] = '%' . $filters[$key] . '%';
            }
        }
        if (($filters['vin'] ?? '') !== '') {
            $where[] = 'EXISTS(SELECT 1 FROM rail_consist_units ru WHERE ru.consist_id=c.id AND ru.vin LIKE :vin)';
            $params['vin'] = '%' . $filters['vin'] . '%';
        }
        if (($filters['usuario'] ?? '') !== '') {
            $where[] = 'u.nombre LIKE :usuario';
            $params['usuario'] = '%' . $filters['usuario'] . '%';
        }
        foreach (['desde' => '>=', 'hasta' => '<='] as $key => $operator) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = "DATE(c.created_at) {$operator} :{$key}";
                $params[$key] = $filters[$key];
            }
        }
        $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM rail_consists c JOIN usuarios u ON u.id=c.created_by' . $clause
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $query = $this->pdo->prepare(
            'SELECT c.*,u.nombre AS created_by_name,v.source_filename
             FROM rail_consists c JOIN usuarios u ON u.id=c.created_by
             JOIN rail_catalog_versions v ON v.id=c.source_catalog_version_id' . $clause .
            ' ORDER BY c.created_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
        );
        $query->execute($params);
        return [
            'items' => $query->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'filters' => $filters,
        ];
    }

    public function recordExport(int $id, int $actorId, string $sha256, string $filename): void
    {
        $this->transaction(function () use ($id, $actorId, $sha256, $filename): void {
            $statement = $this->pdo->prepare(
                'UPDATE rail_consists
                 SET exported_at=CURRENT_TIMESTAMP(6),exported_by=:actor,export_sha256=:sha WHERE id=:id'
            );
            $statement->execute(['actor' => $actorId, 'sha' => $sha256, 'id' => $id]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('No fue posible registrar la exportación.');
            }
            $audit = $this->pdo->prepare(
                'INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_nuevo_json,created_at)
                 VALUES(:actor,\'rail.consist.exportar\',\'rail\',\'rail_consist\',:id,\'exito\',:value,CURRENT_TIMESTAMP(6))'
            );
            $audit->execute([
                'actor' => $actorId,
                'id' => $id,
                'value' => $this->json(['filename' => $filename, 'sha256' => $sha256]),
            ]);
        });
    }

    private function catalogVersion(array $version, int $actorId): int
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO rail_catalog_versions(source_filename,source_sha256,imported_by)
             VALUES(:filename,:sha,:actor)
             ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),active=1'
        );
        $insert->execute([
            'filename' => $version['source_filename'],
            'sha' => $version['source_sha256'],
            'actor' => $actorId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function nextFolio(int $year): string
    {
        $this->pdo->prepare(
            'INSERT INTO rail_consist_sequences(anio,ultimo_folio) VALUES(:year,0)
             ON DUPLICATE KEY UPDATE anio=VALUES(anio)'
        )->execute(['year' => $year]);
        $select = $this->pdo->prepare('SELECT ultimo_folio FROM rail_consist_sequences WHERE anio=:year FOR UPDATE');
        $select->execute(['year' => $year]);
        $next = (int) $select->fetchColumn() + 1;
        $this->pdo->prepare('UPDATE rail_consist_sequences SET ultimo_folio=:next WHERE anio=:year')
            ->execute(['next' => $next, 'year' => $year]);
        return sprintf('CR-%04d-%06d', $year, $next);
    }

    private function hydrateUnit(array $unit): array
    {
        foreach ([
            'final_data_json', 'vehicle_load_data_json', 'shippers_data_json',
            'cnacs_selected_data_json', 'cnacs_additional_data_json', 'trace_json', 'issues_json',
        ] as $field) {
            $unit[$field] = $this->decode($unit[$field] ?? null);
        }
        return $unit;
    }

    private function decode(mixed $value): array
    {
        return $value === null ? [] : (json_decode((string) $value, true) ?: []);
    }

    private function json(array $value): ?string
    {
        return $value === [] ? null : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    private function audit(int $actorId, int $id, string $folio, int $units): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO auditoria_eventos(usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_nuevo_json,created_at)
             VALUES(:actor,\'rail.consist.generar\',\'rail\',\'rail_consist\',:id,\'exito\',:value,CURRENT_TIMESTAMP(6))'
        );
        $statement->execute([
            'actor' => $actorId,
            'id' => $id,
            'value' => $this->json(['folio' => $folio, 'total_units' => $units]),
        ]);
    }

    private function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
