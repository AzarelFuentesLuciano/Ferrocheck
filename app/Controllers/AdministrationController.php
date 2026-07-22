<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth\{AuthenticationRequiredException,Authorization,Csrf,ForbiddenException};
use App\Repositories\{OrganizationalAdminRepository,RoleAdminRepository,UserAdminRepository};
use App\Services\{OrganizationalAdminService,RoleAdminService,UserAdminService};

final class AdministrationController
{
    public function __construct(
        private Authorization $authorization,
        private Csrf $csrf,
        private UserAdminRepository $users,
        private RoleAdminRepository $roles,
        private OrganizationalAdminRepository $organizational,
        private UserAdminService $userService,
        private RoleAdminService $roleService,
        private OrganizationalAdminService $organizationalService,
        private array &$session,
    ) {}

    public function dispatch(string $method,array $query,array $post):void
    {
        try {
            $this->authorization->require('administracion.acceder');
            $section=(string)($query['seccion']??'usuarios');
            match($section){
                'roles'=>$this->guarded('roles.ver',fn()=>$this->roles($method,$query,$post)),
                'areas'=>$this->guarded('areas.ver',fn()=>$this->areas($method,$query,$post)),
                'modulos'=>$this->guarded('modulos.ver',fn()=>$this->modules($method,$query,$post)),
                default=>$this->guarded('usuarios.ver',fn()=>$this->users($method,$query,$post)),
            };
        } catch(ForbiddenException) {
            http_response_code(403);require dirname(__DIR__).'/Views/auth/403.php';
        } catch(AuthenticationRequiredException) {
            $return=rawurlencode($_SERVER['REQUEST_URI']??BASE_URL.'/index.php?modulo=administracion');
            header('Location: '.BASE_URL.'/index.php?modulo=auth&return='.$return,true,302);
        }
    }

    private function guarded(string$permission,callable$callback):mixed{$this->authorization->require($permission);return$callback();}

    private function users(string$method,array$query,array$post):void
    {
        if($method==='POST'){$this->postUser($post);return;}
        $action=(string)($query['accion']??'listar');$user=null;
        if(in_array($action,['editar','password','asignar-area'],true))$user=$this->users->find((int)($query['id']??0));
        $roles=$this->users->roles();$areas=$this->organizational->activeAreas();$modules=$this->organizational->modules();
        $search=trim((string)($query['q']??''));$active=match((string)($query['activo']??'')){'1'=>true,'0'=>false,default=>null};
        $areaFilter=trim((string)($query['area']??''));
        if($action==='exportar-pendientes'){$this->authorization->require('usuarios.ver');$this->exportPendingUsers();return;}
        $page=max(1,(int)($query['pagina']??1));$items=$this->users->list($search,$active,$page,20,$areaFilter);$total=$this->users->count($search,$active,$areaFilter);$organizationalStats=$this->users->organizationalStats();
        $areaPreview=null;if($action==='asignar-area'&&$user&&is_array($this->session['_user_area_preview']??null)&&($this->session['_user_area_preview']['user_id']??0)===(int)$user['id'])$areaPreview=$this->session['_user_area_preview'];
        $csrfToken=$this->csrf->token();$message=$this->consume();require dirname(__DIR__).'/Views/admin/users.php';
    }

    private function postUser(array$post):void
    {
        $this->validCsrf($post);$operation=(string)($post['operation']??'');$id=(int)($post['id']??0);
        if(in_array($operation,['preview_areas','confirm_areas'],true)){$this->authorization->require('areas.asignar');$this->postUserAreas($operation,$id,$post);return;}
        $post['module_allow_ids']=[];$post['module_deny_ids']=[];foreach((array)($post['module_decision']??[])as$moduleId=>$decision){if($decision==='permitir')$post['module_allow_ids'][]=(int)$moduleId;elseif($decision==='denegar')$post['module_deny_ids'][]=(int)$moduleId;}
        $permission=match($operation){'create'=>'usuarios.crear','update'=>'usuarios.editar','activate','deactivate'=>'usuarios.desactivar','password'=>'usuarios.restablecer_password',default=>''};
        if($permission==='')throw new ForbiddenException();$this->authorization->require($permission);
        if(in_array($operation,['create','update'],true))$this->authorization->require('areas.asignar');
        try{
            if($operation==='create')$this->userService->create($post,$this->actorId());
            elseif($operation==='update')$this->userService->update($id,$post,$this->actorId());
            elseif($operation==='password')$this->userService->resetPassword($id,(string)($post['password']??''),(string)($post['password_confirmation']??''),$this->actorId());
            else $this->userService->setActive($id,$operation==='activate',$this->actorId());
            $this->flash('Operación completada.');$this->csrf->rotate();
        }catch(\Throwable$e){$this->flash($e->getMessage());}
        $this->redirect('usuarios');
    }

    private function postUserAreas(string$operation,int$userId,array$post):void
    {
        try{
            $user=$this->users->find($userId)??throw new\DomainException('Usuario no encontrado.');
            if($operation==='preview_areas'){
                $principal=(int)($post['principal_area_id']??0);$ids=array_values(array_unique(array_filter(array_map('intval',(array)($post['area_ids']??[])),static fn(int$id):bool=>$id>0)));if($principal>0&&!in_array($principal,$ids,true))$ids[]=$principal;if($principal<=0||!$this->organizational->validActiveAreaIds($ids))throw new\DomainException('Selecciona una area principal activa y areas adicionales validas.');
                $preview=$this->organizational->userAreaPreview($userId,$ids,$principal);$token=bin2hex(random_bytes(24));$this->session['_user_area_preview']=$preview+['token'=>$token,'user_id'=>$userId,'area_ids'=>$ids,'principal_id'=>$principal];$this->flash('Revisa el impacto y confirma la asignacion.');
            }else{
                $saved=$this->session['_user_area_preview']??null;if(!is_array($saved)||!hash_equals((string)($saved['token']??''),(string)($post['preview_token']??''))||(int)($saved['user_id']??0)!==$userId)throw new\DomainException('La vista previa ya no es valida. Genera una nueva.');
                $this->organizationalService->assignUserAreas($userId,(array)$saved['area_ids'],(int)$saved['principal_id'],$this->actorId());unset($this->session['_user_area_preview']);$this->csrf->rotate();$this->flash('Clasificacion organizacional del usuario actualizada.');$this->redirect('usuarios');
            }
        }catch(\Throwable$e){$this->flash($e->getMessage());}
        header('Location: '.BASE_URL.'/index.php?modulo=administracion&seccion=usuarios&accion=asignar-area&id='.$userId,true,303);exit;
    }

    private function exportPendingUsers():void
    {
        $rows=$this->users->list('',true,1,100000,'unassigned');header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="usuarios-pendientes-area.csv"');echo "\xEF\xBB\xBF";$out=fopen('php://output','wb');fputcsv($out,['Nombre','Empleado','Usuario','Rol','Area principal','Estado']);foreach($rows as$row)fputcsv($out,[$row['nombre'],$row['numero_empleado'],$row['usuario'],$row['roles']?:'Sin rol','Pendiente de asignacion','Activo']);fclose($out);
    }

    private function roles(string$method,array$query,array$post):void
    {
        if($method==='POST'){
            $this->validCsrf($post);$operation=(string)($post['operation']??'');$this->authorization->require($operation==='create'?'roles.crear':'roles.editar');if($operation==='update')$this->authorization->require('roles.asignar_permisos');
            try{$operation==='create'?$this->roleService->create($post,$this->actorId()):$this->roleService->update((int)($post['id']??0),$post,$this->actorId());$this->flash('Rol actualizado.');$this->csrf->rotate();}catch(\Throwable$e){$this->flash($e->getMessage());}
            $this->redirect('roles');return;
        }
        $items=$this->roles->list();$permissions=$this->roles->permissions();$role=isset($query['id'])?$this->roles->find((int)$query['id']):null;$csrfToken=$this->csrf->token();$message=$this->consume();require dirname(__DIR__).'/Views/admin/roles.php';
    }

    private function areas(string$method,array$query,array$post):void
    {
        if($method==='POST'){
            $this->validCsrf($post);$operation=(string)($post['operation']??'');
            $permission=match($operation){'create'=>'areas.crear','update'=>'areas.editar','activate','deactivate'=>'areas.desactivar',default=>''};if($permission==='')throw new ForbiddenException();$this->authorization->require($permission);if($operation==='update')$this->authorization->require('areas.asignar');
            try{if($operation==='create')$this->organizationalService->createArea($post,$this->actorId());elseif($operation==='update'){$id=(int)($post['id']??0);$this->organizationalService->updateArea($id,$post,$this->actorId());$this->organizationalService->assignAreaModules($id,(array)($post['module_ids']??[]),$this->actorId());}else$this->organizationalService->setAreaActive((int)($post['id']??0),$operation==='activate',$this->actorId());$this->flash('Área organizacional actualizada.');$this->csrf->rotate();}catch(\Throwable$e){$this->flash($e->getMessage());}
            $this->redirect('areas');return;
        }
        $search=trim((string)($query['q']??''));$items=$this->organizational->areas($search);$modules=$this->organizational->modules();$area=isset($query['id'])?$this->organizational->findArea((int)$query['id']):null;$csrfToken=$this->csrf->token();$message=$this->consume();require dirname(__DIR__).'/Views/admin/areas.php';
    }

    private function modules(string$method,array$query,array$post):void
    {
        if($method==='POST'){
            $this->validCsrf($post);$this->authorization->require('modulos.asignar');
            try{$this->organizationalService->updateModuleSettings((int)($post['id']??0),(string)($post['nombre']??''),(string)($post['descripcion']??''),(int)($post['orden']??0),($post['activo']??'0')==='1',($post['visible_menu']??'0')==='1',(array)($post['area_ids']??[]),$this->actorId());$this->flash('Módulo actualizado.');$this->csrf->rotate();}catch(\Throwable$e){$this->flash($e->getMessage());}
            $this->redirect('modulos');return;
        }
        $search=trim((string)($query['q']??''));$items=$this->organizational->modules($search);$areas=$this->organizational->activeAreas();$module=isset($query['id'])?$this->organizational->findModule((int)$query['id']):null;$csrfToken=$this->csrf->token();$message=$this->consume();require dirname(__DIR__).'/Views/admin/modules.php';
    }

    private function validCsrf(array$post):void{if(!$this->csrf->validate((string)($post['_csrf']??'')))throw new ForbiddenException();}
    private function actorId():int{return$this->authorization->user()?->id??throw new AuthenticationRequiredException();}
    private function redirect(string$section):never{header('Location: '.BASE_URL.'/index.php?modulo=administracion&seccion='.$section,true,303);exit;}
    private function flash(string$message):void{$this->session['_admin_flash']=$message;}
    private function consume():?string{$message=$this->session['_admin_flash']??null;unset($this->session['_admin_flash']);return is_string($message)?$message:null;}
}
