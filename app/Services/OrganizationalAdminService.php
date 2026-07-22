<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrganizationalAdminRepository;

final class OrganizationalAdminService
{
    public function __construct(private OrganizationalAdminRepository $repository, private GeneralAuditService $audit) {}

    public function createArea(array $input, int $actorId): int
    {
        $data=$this->areaData($input,true);
        try{return $this->transaction(function()use($data,$actorId){$id=$this->repository->createArea($data,$actorId);$this->audit->record($actorId,'area_organizacional.crear','area_organizacional',$id,'exito',[],$data);return$id;});}catch(\PDOException$e){if((string)$e->getCode()==='23000')throw new\DomainException('La clave o el nombre del área ya está registrado.');throw$e;}
    }

    public function updateArea(int $id,array$input,int$actorId):void
    {
        $data=$this->areaData($input,false);
        try{$this->transaction(function()use($id,$data,$actorId){$this->repository->updateArea($id,$data,$actorId);$this->audit->record($actorId,'area_organizacional.editar','area_organizacional',$id,'exito',[],$data);});}catch(\PDOException$e){if((string)$e->getCode()==='23000')throw new\DomainException('El nombre del área ya está registrado.');throw$e;}
    }

    public function setAreaActive(int$id,bool$active,int$actorId):void
    {
        $this->transaction(function()use($id,$active,$actorId){$this->repository->setAreaActive($id,$active,$actorId);$this->audit->record($actorId,$active?'area_organizacional.activar':'area_organizacional.desactivar','area_organizacional',$id,'exito',[],['activo'=>$active]);});
    }

    public function assignUserAreas(int$userId,array$areaIds,int$principalAreaId,int$actorId):void
    {
        $areaIds=$this->ids($areaIds);
        if($userId<=0||$principalAreaId<=0||!in_array($principalAreaId,$areaIds,true))throw new\DomainException('Debe seleccionar exactamente un área principal válida.');
        if(!$this->repository->validActiveAreaIds($areaIds))throw new\DomainException('No se pueden asignar áreas inexistentes o inactivas.');
        $this->transaction(function()use($userId,$areaIds,$principalAreaId,$actorId){$preview=$this->repository->userAreaPreview($userId,$areaIds,$principalAreaId);$before=['areas'=>$preview['old_areas'],'principal'=>$this->repository->userPrincipalAreaId($userId)];$this->repository->replaceUserAreas($userId,$areaIds,$principalAreaId,$actorId);$after=['areas'=>$areaIds,'principal'=>$principalAreaId];$this->audit->record($actorId,'usuario.areas_asignar','usuario',$userId,'exito',$before,$after);foreach(array_diff($areaIds,$preview['old_areas'])as$areaId)$this->audit->record($actorId,'usuario.area_asignar','usuario',$userId,'exito',[],['area_id'=>(int)$areaId,'es_principal'=>$areaId===$principalAreaId]);foreach(array_diff($preview['old_areas'],$areaIds)as$areaId)$this->audit->record($actorId,'usuario.area_retirar','usuario',$userId,'exito',['area_id'=>(int)$areaId],[]);if($before['principal']!==$principalAreaId)$this->audit->record($actorId,'usuario.area_principal_cambiar','usuario',$userId,'exito',['area_id'=>$before['principal']],['area_id'=>$principalAreaId]);});
    }

    public function assignAreaModules(int$areaId,array$moduleIds,int$actorId):void
    {
        $moduleIds=$this->ids($moduleIds);
        if($areaId<=0||!$this->repository->validActiveAreaIds([$areaId]))throw new\DomainException('Área organizacional inactiva o inexistente.');
        if($moduleIds!==[]&&!$this->repository->validActiveModuleIds($moduleIds))throw new\DomainException('Módulo inactivo o inexistente.');
        $this->transaction(function()use($areaId,$moduleIds,$actorId){$before=$this->repository->areaModuleIds($areaId);$this->repository->replaceAreaModules($areaId,$moduleIds,$actorId);foreach(array_diff($moduleIds,$before)as$id)$this->audit->record($actorId,'area_organizacional.modulo_asociar','area_organizacional',$areaId,'exito',[],['modulo_id'=>(int)$id]);foreach(array_diff($before,$moduleIds)as$id)$this->audit->record($actorId,'area_organizacional.modulo_retirar','area_organizacional',$areaId,'exito',['modulo_id'=>(int)$id],[]);});
    }

    public function setUserModuleDecision(int$userId,int$moduleId,string$type,bool$active,int$actorId):void
    {
        if(!in_array($type,['permitir','denegar'],true))throw new\DomainException('Tipo de excepción de módulo inválido.');
        if($userId<=0||!$this->repository->validActiveModuleIds([$moduleId]))throw new\DomainException('Usuario o módulo inválido.');
        $this->transaction(function()use($userId,$moduleId,$type,$active,$actorId){$this->repository->setUserModuleDecision($userId,$moduleId,$type,$active,$actorId);$this->audit->record($actorId,'usuario.modulo_configurar','usuario',$userId,'exito',[],['modulo_id'=>$moduleId,'tipo'=>$type,'activo'=>$active]);});
    }

    public function updateModuleSettings(int$moduleId,string$name,?string$description,int$order,bool$active,bool$visible,array$areaIds,int$actorId):void
    {
        $name=trim($name);$description=trim((string)$description);$areaIds=$this->ids($areaIds);
        if($moduleId<=0||$name===''||mb_strlen($name)>120||mb_strlen($description)>500||$order<0||$order>65535)throw new\DomainException('Configuración de módulo inválida.');
        if($areaIds!==[]&&!$this->repository->validActiveAreaIds($areaIds))throw new\DomainException('No se pueden asociar áreas inactivas o inexistentes.');
        $this->transaction(function()use($moduleId,$name,$description,$order,$active,$visible,$areaIds,$actorId){$before=$this->repository->findModule($moduleId)??throw new\DomainException('Módulo no encontrado.');$this->repository->updateModuleSettings($moduleId,$name,$description===''?null:$description,$order,$active,$visible);$this->repository->replaceModuleAreas($moduleId,$areaIds,$actorId);$this->audit->record($actorId,'modulo.configurar','modulo',$moduleId,'exito',['areas'=>$before['area_ids']],['nombre'=>$name,'orden'=>$order,'activo'=>$active,'visible_menu'=>$visible,'areas'=>$areaIds]);});
    }

    private function areaData(array$input,bool$creating):array
    {
        $key=mb_strtolower(trim((string)($input['clave']??'')));$name=(string)preg_replace('/\s+/u',' ',trim((string)($input['nombre']??'')));$description=trim((string)($input['descripcion']??''));
        if($creating&&!preg_match('/^[a-z0-9_]{2,80}$/',$key))throw new\DomainException('Clave de área inválida.');
        if($name===''||mb_strlen($name)>120)throw new\DomainException('Nombre de área inválido.');
        if(mb_strlen($description)>500)throw new\DomainException('Descripción demasiado extensa.');
        return['clave'=>$key,'nombre'=>$name,'descripcion'=>$description===''?null:$description];
    }
    private function ids(array$ids):array{return array_values(array_unique(array_filter(array_map('intval',$ids),fn(int$id):bool=>$id>0)));}
    private function transaction(callable$callback):mixed{$pdo=$this->repository->pdo();$pdo->beginTransaction();try{$result=$callback();$pdo->commit();return$result;}catch(\Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}}
}
