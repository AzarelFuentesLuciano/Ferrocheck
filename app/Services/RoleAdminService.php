<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\RoleAdminRepository;

final class RoleAdminService
{
    public function __construct(private RoleAdminRepository$roles,private GeneralAuditService$audit){}
    public function create(array$input,int$actor):int{return$this->transaction(function()use($input,$actor){[$name,$description,$active,$permissions]=$this->validate($input);if(!$active)throw new \DomainException('Un rol nuevo debe iniciar activo.');if($this->roles->duplicateName($name))throw new \DomainException('El rol ya existe.');$id=$this->roles->insert($name,$description);$this->roles->replacePermissions($id,$permissions,$actor);$this->audit->record($actor,'rol.crear','rol',$id,'exito',[],['permisos'=>$permissions]);return$id;});}
    public function update(int$id,array$input,int$actor):void{$this->transaction(function()use($id,$input,$actor){$before=$this->roles->find($id)??throw new \DomainException('Rol no encontrado.');[$name,$description,$active,$permissions]=$this->validate($input);if($this->roles->duplicateName($name,$id))throw new \DomainException('El rol ya existe.');$critical=$this->roles->allCriticalPermissionIds();if((!$active||array_diff($critical,$permissions))&&$this->roles->activeAdministratorsExcludingRole($id)===0)throw new \DomainException('No se puede dejar el sistema sin un rol administrativo efectivo.');if((bool)$before['es_sistema']&&$before['nombre']==='Administrador'){$name='Administrador';}$this->roles->update($id,$name,$description,$active);$this->roles->replacePermissions($id,$permissions,$actor);$this->audit->record($actor,'rol.editar','rol',$id,'exito',['activo'=>(bool)$before['activo'],'permisos'=>$before['permission_ids']],['activo'=>$active,'permisos'=>$permissions]);});}
    private function validate(array$i):array{$name=trim((string)($i['nombre']??''));$description=trim((string)($i['descripcion']??''));if($name===''||mb_strlen($name)>80)throw new \DomainException('Nombre de rol inválido.');if(mb_strlen($description)>255)throw new \DomainException('Descripción demasiado larga.');$active=filter_var($i['activo']??null,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE);if($active===null)throw new \DomainException('Estado inválido.');$permissions=array_values(array_unique(array_filter(array_map('intval',(array)($i['permission_ids']??[])),fn($v)=>$v>0)));if(!$this->roles->validPermissions($permissions))throw new \DomainException('Permiso inválido.');return[$name,$description===''?null:$description,$active,$permissions];}
    private function transaction(callable$fn):mixed{$pdo=$this->roles->pdo();$pdo->beginTransaction();try{$result=$fn();$pdo->commit();return$result;}catch(\Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}}
}
