<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Auth\OrganizationalAccessRepositoryInterface;

final class OrganizationalAccessRepository implements OrganizationalAccessRepositoryInterface
{
    public function __construct(private \PDO $pdo) {}

    public function findActiveModule(string $moduleKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id,clave,nombre,ruta,icono,orden,visible_menu FROM modulos WHERE clave=:clave AND activo=1 LIMIT 1');
        $stmt->execute(['clave' => $moduleKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function individualModuleDecision(int $userId, int $moduleId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT tipo FROM usuario_modulos WHERE usuario_id=:usuario AND modulo_id=:modulo AND activo=1 AND tipo IN('permitir','denegar') LIMIT 1");
        $stmt->execute(['usuario' => $userId, 'modulo' => $moduleId]);
        $decision = $stmt->fetchColumn();
        return is_string($decision) ? $decision : null;
    }

    public function inheritsModuleFromActiveArea(int $userId, int $moduleId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuario_areas ua JOIN areas_organizacionales a ON a.id=ua.area_id AND a.activo=1 JOIN area_modulos am ON am.area_id=a.id AND am.activo=1 WHERE ua.usuario_id=:usuario AND ua.activo=1 AND am.modulo_id=:modulo LIMIT 1');
        $stmt->execute(['usuario' => $userId, 'modulo' => $moduleId]);
        return $stmt->fetchColumn() !== false;
    }

    public function activeAreaIdsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT a.id FROM usuario_areas ua JOIN areas_organizacionales a ON a.id=ua.area_id WHERE ua.usuario_id=:usuario AND ua.activo=1 AND a.activo=1 ORDER BY ua.es_principal DESC,a.nombre');
        $stmt->execute(['usuario' => $userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function activeAreaExists(int $areaId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM areas_organizacionales WHERE id=:id AND activo=1 LIMIT 1');
        $stmt->execute(['id' => $areaId]);
        return $stmt->fetchColumn() !== false;
    }

    public function visibleActiveModules(): array
    {
        return $this->pdo->query('SELECT id,clave,nombre,descripcion,ruta,icono,orden FROM modulos WHERE activo=1 AND visible_menu=1 ORDER BY orden,nombre')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function userHasActiveArea(int$userId):bool{$stmt=$this->pdo->prepare('SELECT 1 FROM usuario_areas ua JOIN areas_organizacionales a ON a.id=ua.area_id AND a.activo=1 WHERE ua.usuario_id=:id AND ua.activo=1 LIMIT 1');$stmt->execute(['id'=>$userId]);return$stmt->fetchColumn()!==false;}
    public function userHasActiveModuleDecision(int$userId):bool{$stmt=$this->pdo->prepare('SELECT 1 FROM usuario_modulos WHERE usuario_id=:id AND activo=1 LIMIT 1');$stmt->execute(['id'=>$userId]);return$stmt->fetchColumn()!==false;}
}
