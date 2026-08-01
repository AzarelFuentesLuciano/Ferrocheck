<?php

declare(strict_types=1);

namespace App\Repositories\Rail;

use PDO;
use RuntimeException;
use Throwable;

class RouteCodeCatalogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function activeCatalog(): ?array
    {
        $version = $this->pdo->query(
            'SELECT id,source_filename,source_sha256,imported_by,imported_at,active,origin
             FROM rail_catalog_versions WHERE active=1 ORDER BY imported_at DESC,id DESC LIMIT 1'
        )->fetch();
        if (!is_array($version)) {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT id,source_row,route_code,route_king,market,shipping_destination,carrier,
                    heavy_duty,usa_canada,production_plant,rc_plant,load_by,border_crossing
             FROM rail_catalog_route_codes WHERE catalog_version_id=:version ORDER BY source_row,id'
        );
        $statement->execute(['version' => $version['id']]);
        $routes = [];
        $variants = [];
        $recordCount = 0;
        foreach ($statement->fetchAll() as $row) {
            $code = (string) $row['route_code'];
            $routes[$code] ??= $row;
            $variants[$code][] = $row;
            $recordCount++;
        }
        if ($routes === []) {
            return null;
        }
        return [
            'version' => [
                'id' => (int) $version['id'],
                'source_filename' => (string) $version['source_filename'],
                'source_sha256' => (string) $version['source_sha256'],
                'imported_by' => $version['imported_by'] === null ? null : (int) $version['imported_by'],
                'imported_at' => (string) $version['imported_at'],
                'active' => true,
                'origin' => (string) ($version['origin'] ?? 'manual'),
                'record_count' => $recordCount,
            ],
            'routes' => $routes,
            'route_variants' => $variants,
            'duplicate_routes' => array_filter($variants, static fn (array $rows): bool => count($rows) > 1),
        ];
    }

    public function import(array $catalog, int $actorId): array
    {
        return $this->transaction(function () use ($catalog, $actorId): array {
            $version = (array) ($catalog['version'] ?? []);
            $routes = array_values((array) ($catalog['records'] ?? $catalog['routes'] ?? []));
            if ($routes === [] || !preg_match('/^[a-f0-9]{64}$/', (string) ($version['source_sha256'] ?? ''))) {
                throw new RuntimeException('El catálogo validado no contiene rutas importables.');
            }

            $existing = $this->pdo->prepare(
                'SELECT id FROM rail_catalog_versions WHERE source_sha256=:sha LIMIT 1'
            );
            $existing->execute(['sha' => $version['source_sha256']]);
            $versionId = (int) ($existing->fetchColumn() ?: 0);

            $this->pdo->exec('UPDATE rail_catalog_versions SET active=0 WHERE active=1');
            if ($versionId > 0) {
                $update = $this->pdo->prepare(
                    'UPDATE rail_catalog_versions
                     SET source_filename=:filename,imported_by=:actor,imported_at=CURRENT_TIMESTAMP,active=1
                     WHERE id=:id'
                );
                $update->execute([
                    'filename' => $version['source_filename'],
                    'actor' => $actorId,
                    'id' => $versionId,
                ]);
                $this->pdo->prepare('DELETE FROM rail_catalog_route_codes WHERE catalog_version_id=:id')
                    ->execute(['id' => $versionId]);
            } else {
                $insert = $this->pdo->prepare(
                    'INSERT INTO rail_catalog_versions(source_filename,source_sha256,imported_by,active)
                     VALUES(:filename,:sha,:actor,1)'
                );
                $insert->execute([
                    'filename' => $version['source_filename'],
                    'sha' => $version['source_sha256'],
                    'actor' => $actorId,
                ]);
                $versionId = (int) $this->pdo->lastInsertId();
            }

            $insertRoute = $this->pdo->prepare(
                'INSERT INTO rail_catalog_route_codes
                 (catalog_version_id,source_row,route_code,route_king,market,shipping_destination,carrier,
                  heavy_duty,usa_canada,production_plant,rc_plant,load_by,border_crossing)
                 VALUES(:version,:row,:code,:king,:market,:destination,:carrier,
                        :heavy_duty,:usa_canada,:production_plant,:rc_plant,:load_by,:border_crossing)'
            );
            foreach ($routes as $route) {
                $insertRoute->execute([
                    'version' => $versionId,
                    'row' => (int) ($route['source_row'] ?? 0),
                    'code' => (string) ($route['route_code'] ?? ''),
                    'king' => $this->nullable($route['route_king'] ?? null),
                    'market' => $this->nullable($route['market'] ?? null),
                    'destination' => $this->nullable($route['shipping_destination'] ?? null),
                    'carrier' => $this->nullable($route['carrier'] ?? null),
                    'heavy_duty' => $this->nullable($route['heavy_duty'] ?? null),
                    'usa_canada' => $this->nullable($route['usa_canada'] ?? null),
                    'production_plant' => $this->nullable($route['production_plant'] ?? null),
                    'rc_plant' => $this->nullable($route['rc_plant'] ?? null),
                    'load_by' => $this->nullable($route['load_by'] ?? null),
                    'border_crossing' => $this->nullable($route['border_crossing'] ?? null),
                ]);
            }
            $audit = $this->pdo->prepare(
                'INSERT INTO auditoria_eventos
                 (usuario_id,accion,modulo,entidad,entidad_id,resultado,valor_nuevo_json,created_at)
                 VALUES(:actor,\'rail.catalogos.importar\',\'rail\',\'rail_catalog_version\',:id,
                        \'exito\',:value,CURRENT_TIMESTAMP)'
            );
            $audit->execute([
                'actor' => $actorId,
                'id' => $versionId,
                'value' => json_encode([
                    'source_filename' => $version['source_filename'],
                    'source_sha256' => $version['source_sha256'],
                    'record_count' => count($routes),
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
            return $this->activeCatalog()
                ?? throw new RuntimeException('El catálogo importado no pudo activarse.');
        });
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
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
