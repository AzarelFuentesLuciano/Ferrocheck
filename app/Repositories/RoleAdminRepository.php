<?php
declare(strict_types=1);
namespace App\Repositories;

final class RoleAdminRepository
{
    public function __construct(private \PDO$pdo){}
    public function pdo():\PDO{return$this->pdo;}
    public function list():array{return$this->pdo->query('SELECT r.id,r.nombre,r.descripcion,r.activo,r.es_sistema,COUNT(DISTINCT ur.usuario_id) usuarios,COUNT(DISTINCT rp.permiso_id) permisos FROM roles r LEFT JOIN usuario_roles ur ON ur.rol_id=r.id LEFT JOIN rol_permisos rp ON rp.rol_id=r.id GROUP BY r.id ORDER BY r.nombre')->fetchAll(\PDO::FETCH_ASSOC);}
    public function permissions():array{return$this->pdo->query('SELECT id,clave,nombre,descripcion FROM permisos ORDER BY clave')->fetchAll(\PDO::FETCH_ASSOC);}
    public function find(int$id):?array{$s=$this->pdo->prepare('SELECT id,nombre,descripcion,activo,es_sistema FROM roles WHERE id=:id');$s->execute(['id'=>$id]);$r=$s->fetch(\PDO::FETCH_ASSOC);if(!is_array($r))return null;$r['permission_ids']=$this->permissionIds($id);return$r;}
    public function permissionIds(int$id):array{$s=$this->pdo->prepare('SELECT permiso_id FROM rol_permisos WHERE rol_id=:id');$s->execute(['id'=>$id]);return array_map('intval',$s->fetchAll(\PDO::FETCH_COLUMN));}
    public function validPermissions(array$ids):bool{if($ids===[])return true;$s=$this->pdo->prepare('SELECT COUNT(*) FROM permisos WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).')');$s->execute($ids);return(int)$s->fetchColumn()===count($ids);}
    public function duplicateName(string$name,?int$except=null):bool{$s=$this->pdo->prepare('SELECT 1 FROM roles WHERE nombre=:name'.($except?' AND id<>:id':'').' LIMIT 1');$p=['name'=>$name];if($except)$p['id']=$except;$s->execute($p);return$s->fetchColumn()!==false;}
    public function insert(string$name,?string$description):int{$s=$this->pdo->prepare('INSERT INTO roles(nombre,descripcion,activo,es_sistema)VALUES(:name,:description,1,0)');$s->execute(['name'=>$name,'description'=>$description]);return(int)$this->pdo->lastInsertId();}
    public function update(int$id,string$name,?string$description,bool$active):void{$s=$this->pdo->prepare('UPDATE roles SET nombre=:name,descripcion=:description,activo=:active WHERE id=:id');$s->execute(['name'=>$name,'description'=>$description,'active'=>(int)$active,'id'=>$id]);}
    public function replacePermissions(int$id,array$permissionIds,int$actor):void{$this->pdo->prepare('DELETE FROM rol_permisos WHERE rol_id=:id')->execute(['id'=>$id]);$s=$this->pdo->prepare('INSERT INTO rol_permisos(rol_id,permiso_id,asignado_por)VALUES(:rol,:permiso,:actor)');foreach($permissionIds as$permission)$s->execute(['rol'=>$id,'permiso'=>$permission,'actor'=>$actor]);}
    public function allCriticalPermissionIds():array{$s=$this->pdo->query("SELECT id FROM permisos WHERE clave IN ('usuarios.crear','usuarios.editar','administracion.acceder')");return array_map('intval',$s->fetchAll(\PDO::FETCH_COLUMN));}
    public function activeAdministratorsExcludingRole(int$roleId):int{$s=$this->pdo->prepare("SELECT u.id FROM usuarios u JOIN usuario_roles ur ON ur.usuario_id=u.id JOIN rol_permisos rp ON rp.rol_id=ur.rol_id JOIN permisos p ON p.id=rp.permiso_id WHERE u.activo=1 AND ur.rol_id<>:role AND p.clave IN ('usuarios.crear','usuarios.editar','administracion.acceder') GROUP BY u.id HAVING COUNT(DISTINCT p.clave)=3");$s->execute(['role'=>$roleId]);return count($s->fetchAll());}
}
