<?php
declare(strict_types=1);

namespace App\Repositories;

final class OrganizationalAdminRepository
{
    public function __construct(private \PDO $pdo) {}
    public function pdo(): \PDO { return $this->pdo; }

    public function activeAreas(): array
    {
        return $this->pdo->query('SELECT id,clave,nombre,descripcion,activo FROM areas_organizacionales WHERE activo=1 ORDER BY nombre')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function areas(string $search = ''): array
    {
        $sql = 'SELECT a.id,a.clave,a.nombre,a.descripcion,a.activo,COUNT(DISTINCT ua.usuario_id) usuarios,COUNT(DISTINCT am.modulo_id) modulos FROM areas_organizacionales a LEFT JOIN usuario_areas ua ON ua.area_id=a.id AND ua.activo=1 LEFT JOIN area_modulos am ON am.area_id=a.id AND am.activo=1';
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE a.nombre LIKE :search ESCAPE '!' OR a.clave LIKE :search ESCAPE '!'";
            $params['search'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        }
        $sql .= ' GROUP BY a.id ORDER BY a.activo DESC,a.nombre';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function modules(string $search = ''): array
    {
        $sql='SELECT m.id,m.clave,m.nombre,m.descripcion,m.ruta,m.icono,m.orden,m.activo,m.visible_menu,COUNT(DISTINCT am.area_id) areas,COUNT(DISTINCT um.usuario_id) excepciones FROM modulos m LEFT JOIN area_modulos am ON am.modulo_id=m.id AND am.activo=1 LEFT JOIN usuario_modulos um ON um.modulo_id=m.id AND um.activo=1';$params=[];
        if($search!==''){$sql.=" WHERE m.nombre LIKE :search ESCAPE '!' OR m.clave LIKE :search ESCAPE '!'";$params['search']='%'.str_replace(['!','%','_'],['!!','!%','!_'],$search).'%';}
        $sql.=' GROUP BY m.id ORDER BY m.orden,m.nombre';$stmt=$this->pdo->prepare($sql);$stmt->execute($params);return$stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findArea(int$id):?array{$stmt=$this->pdo->prepare('SELECT id,clave,nombre,descripcion,activo FROM areas_organizacionales WHERE id=:id');$stmt->execute(['id'=>$id]);$row=$stmt->fetch(\PDO::FETCH_ASSOC);if(!is_array($row))return null;$row['module_ids']=$this->areaModuleIds($id);return$row;}
    public function findModule(int$id):?array{$stmt=$this->pdo->prepare('SELECT id,clave,nombre,descripcion,ruta,icono,orden,activo,visible_menu FROM modulos WHERE id=:id');$stmt->execute(['id'=>$id]);$row=$stmt->fetch(\PDO::FETCH_ASSOC);if(!is_array($row))return null;$row['area_ids']=$this->moduleAreaIds($id);return$row;}
    public function areaModuleIds(int$id):array{$stmt=$this->pdo->prepare('SELECT modulo_id FROM area_modulos WHERE area_id=:id AND activo=1');$stmt->execute(['id'=>$id]);return array_map('intval',$stmt->fetchAll(\PDO::FETCH_COLUMN));}
    public function moduleAreaIds(int$id):array{$stmt=$this->pdo->prepare('SELECT area_id FROM area_modulos WHERE modulo_id=:id AND activo=1');$stmt->execute(['id'=>$id]);return array_map('intval',$stmt->fetchAll(\PDO::FETCH_COLUMN));}

    public function validActiveAreaIds(array $ids): bool
    {
        return $this->validIds('areas_organizacionales', $ids);
    }

    public function validActiveModuleIds(array $ids): bool
    {
        return $this->validIds('modulos', $ids);
    }

    public function createArea(array $data, int $actorId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO areas_organizacionales(clave,nombre,descripcion,activo,created_by,updated_by) VALUES(:clave,:nombre,:descripcion,1,:actor,:actor2)');
        $stmt->execute(['clave'=>$data['clave'],'nombre'=>$data['nombre'],'descripcion'=>$data['descripcion'],'actor'=>$actorId,'actor2'=>$actorId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateArea(int $id, array $data, int $actorId): void
    {
        $stmt = $this->pdo->prepare('UPDATE areas_organizacionales SET nombre=:nombre,descripcion=:descripcion,updated_by=:actor WHERE id=:id');
        $stmt->execute(['nombre'=>$data['nombre'],'descripcion'=>$data['descripcion'],'actor'=>$actorId,'id'=>$id]);
        if ($stmt->rowCount() === 0 && !$this->exists('areas_organizacionales', $id)) throw new \DomainException('Área organizacional no encontrada.');
    }

    public function setAreaActive(int $id, bool $active, int $actorId): void
    {
        if (!$active && $this->areaHasActiveAssignments($id)) throw new \DomainException('No se puede desactivar un área con asignaciones activas.');
        $stmt=$this->pdo->prepare('UPDATE areas_organizacionales SET activo=:activo,updated_by=:actor WHERE id=:id');
        $stmt->execute(['activo'=>(int)$active,'actor'=>$actorId,'id'=>$id]);
        if ($stmt->rowCount() === 0 && !$this->exists('areas_organizacionales', $id)) throw new \DomainException('Área organizacional no encontrada.');
    }

    public function replaceUserAreas(int $userId, array $areaIds, int $principalAreaId, int $actorId): void
    {
        $this->pdo->prepare('UPDATE usuario_areas SET activo=0,es_principal=0 WHERE usuario_id=:usuario')->execute(['usuario'=>$userId]);
        $sql='INSERT INTO usuario_areas(usuario_id,area_id,es_principal,activo,created_by) VALUES(:usuario,:area,:principal,1,:actor) ON DUPLICATE KEY UPDATE es_principal=VALUES(es_principal),activo=1';
        $stmt=$this->pdo->prepare($sql);
        foreach($areaIds as $areaId)$stmt->execute(['usuario'=>$userId,'area'=>$areaId,'principal'=>(int)($areaId===$principalAreaId),'actor'=>$actorId]);
    }

    public function replaceAreaModules(int $areaId, array $moduleIds, int $actorId): void
    {
        $this->pdo->prepare('UPDATE area_modulos SET activo=0 WHERE area_id=:area')->execute(['area'=>$areaId]);
        $stmt=$this->pdo->prepare('INSERT INTO area_modulos(area_id,modulo_id,activo,created_by) VALUES(:area,:modulo,1,:actor) ON DUPLICATE KEY UPDATE activo=1');
        foreach($moduleIds as$moduleId)$stmt->execute(['area'=>$areaId,'modulo'=>$moduleId,'actor'=>$actorId]);
    }

    public function replaceModuleAreas(int$moduleId,array$areaIds,int$actorId):void
    {
        $this->pdo->prepare('UPDATE area_modulos SET activo=0 WHERE modulo_id=:modulo')->execute(['modulo'=>$moduleId]);
        $stmt=$this->pdo->prepare('INSERT INTO area_modulos(area_id,modulo_id,activo,created_by) VALUES(:area,:modulo,1,:actor) ON DUPLICATE KEY UPDATE activo=1');
        foreach($areaIds as$areaId)$stmt->execute(['area'=>$areaId,'modulo'=>$moduleId,'actor'=>$actorId]);
    }

    public function setUserModuleDecision(int $userId, int $moduleId, string $type, bool $active, int $actorId): void
    {
        $stmt=$this->pdo->prepare('INSERT INTO usuario_modulos(usuario_id,modulo_id,tipo,activo,created_by) VALUES(:usuario,:modulo,:tipo,:activo,:actor) ON DUPLICATE KEY UPDATE tipo=VALUES(tipo),activo=VALUES(activo)');
        $stmt->execute(['usuario'=>$userId,'modulo'=>$moduleId,'tipo'=>$type,'activo'=>(int)$active,'actor'=>$actorId]);
    }
    public function inheritedModulesForAreas(array$areaIds):array{if($areaIds===[])return[];$marks=implode(',',array_fill(0,count($areaIds),'?'));$stmt=$this->pdo->prepare("SELECT DISTINCT m.id,m.clave,m.nombre FROM modulos m JOIN area_modulos am ON am.modulo_id=m.id AND am.activo=1 JOIN areas_organizacionales a ON a.id=am.area_id AND a.activo=1 WHERE m.activo=1 AND a.id IN($marks) ORDER BY m.orden,m.nombre");$stmt->execute($areaIds);return$stmt->fetchAll(\PDO::FETCH_ASSOC);}
    public function userPrincipalAreaId(int$userId):?int{$stmt=$this->pdo->prepare('SELECT area_id FROM usuario_areas WHERE usuario_id=:id AND activo=1 AND es_principal=1 LIMIT 1');$stmt->execute(['id'=>$userId]);$id=$stmt->fetchColumn();return$id===false?null:(int)$id;}
    public function userAreaPreview(int$userId,array$newAreaIds,int$newPrincipalId):array{$user=$this->pdo->prepare("SELECT u.id,u.nombre,u.usuario,GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') roles FROM usuarios u LEFT JOIN usuario_roles ur ON ur.usuario_id=u.id LEFT JOIN roles r ON r.id=ur.rol_id WHERE u.id=:id GROUP BY u.id");$user->execute(['id'=>$userId]);$row=$user->fetch(\PDO::FETCH_ASSOC);if(!is_array($row))throw new\DomainException('Usuario no encontrado.');$oldIds=[];$s=$this->pdo->prepare('SELECT area_id FROM usuario_areas WHERE usuario_id=:id AND activo=1');$s->execute(['id'=>$userId]);$oldIds=array_map('intval',$s->fetchAll(\PDO::FETCH_COLUMN));$principal=$this->pdo->prepare('SELECT nombre FROM areas_organizacionales WHERE id=:id AND activo=1');$principal->execute(['id'=>$newPrincipalId]);$principalName=$principal->fetchColumn();if($principalName===false)throw new\DomainException('Área principal inactiva o inexistente.');$old=$this->inheritedModulesForAreas($oldIds);$new=$this->inheritedModulesForAreas($newAreaIds);$exceptions=$this->pdo->prepare('SELECT m.nombre,um.tipo FROM usuario_modulos um JOIN modulos m ON m.id=um.modulo_id WHERE um.usuario_id=:id AND um.activo=1 ORDER BY m.orden');$exceptions->execute(['id'=>$userId]);return['user'=>$row,'principal'=>['id'=>$newPrincipalId,'nombre'=>$principalName],'old_areas'=>$oldIds,'new_areas'=>$newAreaIds,'gained'=>array_values(array_diff(array_column($new,'nombre'),array_column($old,'nombre'))),'lost'=>array_values(array_diff(array_column($old,'nombre'),array_column($new,'nombre'))),'inherited'=>array_column($new,'nombre'),'exceptions'=>$exceptions->fetchAll(\PDO::FETCH_ASSOC)];}

    public function updateModuleSettings(int $moduleId,string$name,?string$description,int $order, bool $active, bool $visible): void
    {
        $stmt=$this->pdo->prepare('UPDATE modulos SET nombre=:nombre,descripcion=:descripcion,orden=:orden,activo=:activo,visible_menu=:visible WHERE id=:id');
        $stmt->execute(['nombre'=>$name,'descripcion'=>$description,'orden'=>$order,'activo'=>(int)$active,'visible'=>(int)$visible,'id'=>$moduleId]);
        if($stmt->rowCount()===0&&!$this->exists('modulos',$moduleId))throw new\DomainException('Módulo no encontrado.');
    }

    private function validIds(string $table, array $ids): bool
    {
        if ($ids === []) return false;
        $ids=array_values(array_unique(array_map('intval',$ids)));
        $marks=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE activo=1 AND id IN ({$marks})");
        $stmt->execute($ids);
        return (int)$stmt->fetchColumn()===count($ids);
    }

    private function exists(string $table,int$id):bool{$stmt=$this->pdo->prepare("SELECT 1 FROM {$table} WHERE id=:id");$stmt->execute(['id'=>$id]);return$stmt->fetchColumn()!==false;}
    private function areaHasActiveAssignments(int$id):bool{$stmt=$this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM usuario_areas WHERE area_id=:id AND activo=1) OR EXISTS(SELECT 1 FROM area_modulos WHERE area_id=:id2 AND activo=1)');$stmt->execute(['id'=>$id,'id2'=>$id]);return(bool)$stmt->fetchColumn();}
}
