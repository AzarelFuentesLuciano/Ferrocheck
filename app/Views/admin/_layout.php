<?php
declare(strict_types=1);
$base = rtrim((string) BASE_URL, '/');
$can = fn (string $permission): bool => $this->authorization->can($permission);
$modules = [
    ['id'=>'dashboard','label'=>'Dashboard','icon'=>'⌂','url'=>$base.'/index.php?modulo=dashboard'],
    ['id'=>'ferrocheck','label'=>'FerroCheck','icon'=>'▰','url'=>$base.'/index.php?modulo=ferrocheck&seccion=dashboard'],
    ['id'=>'inventario-material','label'=>'Inventario de Material','icon'=>'▣','url'=>$base.'/index.php?modulo=inventario-material'],
    ['id'=>'operaciones-patio','label'=>'Inventario de Patio','icon'=>'▤','url'=>$base.'/index.php?modulo=operaciones-patio'],
    ['id'=>'control-escaneres','label'=>'Control de Escáneres','icon'=>'▦','url'=>$base.'/index.php?modulo=control-escaneres'],
    ['id'=>'reportes','label'=>'Reportes','icon'=>'▥','url'=>$base.'/index.php?modulo=reportes'],
];
if ($can('administracion.acceder')) {
    $sections = [];
    if ($can('usuarios.ver')) $sections[] = ['id'=>'usuarios','label'=>'Usuarios','url'=>$base.'/index.php?modulo=administracion&seccion=usuarios'];
    if ($can('roles.ver')) $sections[] = ['id'=>'roles','label'=>'Roles y permisos','url'=>$base.'/index.php?modulo=administracion&seccion=roles'];
    if ($can('areas.ver')) $sections[] = ['id'=>'areas','label'=>'Áreas','url'=>$base.'/index.php?modulo=administracion&seccion=areas'];
    if ($can('modulos.ver')) $sections[] = ['id'=>'modulos','label'=>'Módulos','url'=>$base.'/index.php?modulo=administracion&seccion=modulos'];
    $modules[] = ['id'=>'administracion','label'=>'Administración','icon'=>'⚙','url'=>$sections[0]['url'] ?? '#','sections'=>$sections];
}
$modules[]=['id'=>'configuracion-general','label'=>'Configuración General','icon'=>'◉','url'=>$base.'/index.php?modulo=configuracion-general'];
$authorizedKeys=(array)($_SESSION['auth_module_keys']??[]);
if($authorizedKeys!==[]){$keyById=['dashboard'=>'dashboard','ferrocheck'=>'ferrocheck','inventario-material'=>'inventario_material','operaciones-patio'=>'inventario_patio','control-escaneres'=>'control_escaneres','reportes'=>'reportes','administracion'=>'administracion','configuracion-general'=>'configuracion_general'];$modules=array_values(array_filter($modules,fn(array$m):bool=>in_array($keyById[$m['id']]??'', $authorizedKeys,true)));}
$pageTitle = ($adminTitle ?? 'Administración').' | VASCOR OPS';
$assetBaseUrl = $base;
$activeModule = 'administracion';
$activeSection = $adminSection ?? 'usuarios';
$content = $adminContent ?? '';
$moduleNavigation='<section class="admin-module-hero"><p class="eyebrow">Administración</p><h1>Administración del sistema</h1><p>Gestiona usuarios, roles, áreas, módulos, permisos y accesos.</p><nav class="admin-tabs" aria-label="Secciones de Administración">'.($can('usuarios.ver')?'<a class="'.($activeSection==='usuarios'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=usuarios"'.($activeSection==='usuarios'?' aria-current="page"':'').'>Usuarios</a>':'').($can('roles.ver')?'<a class="'.($activeSection==='roles'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=roles"'.($activeSection==='roles'?' aria-current="page"':'').'>Roles y permisos</a>':'').($can('areas.ver')?'<a class="'.($activeSection==='areas'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=areas"'.($activeSection==='areas'?' aria-current="page"':'').'>Áreas</a>':'').($can('modulos.ver')?'<a class="'.($activeSection==='modulos'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=modulos"'.($activeSection==='modulos'?' aria-current="page"':'').'>Módulos</a>':'').'</nav></section>';
$additionalStyles = [$base.'/assets/css/admin.css',$base.'/assets/css/admin-shell.css'];
$additionalScripts = [$base.'/assets/js/admin.js'];
$user = $this->authorization->user();
$header = ['systemSubtitle'=>'Plataforma Operativa','currentUser'=>$user->name,'currentRole'=>$user->roles[0]??'Usuario','logoutUrl'=>$base.'/index.php?modulo=auth&accion=logout','logoutCsrf'=>$csrfToken];
require dirname(__DIR__).'/layouts/app.php';
