<?php
declare(strict_types=1);
use App\Support\AuthenticatedHeaderBuilder;
$base = rtrim((string) BASE_URL, '/');
$can = fn (string $permission): bool => $this->authorization->can($permission);
$modules = $navigationModules;
$pageTitle = ($adminTitle ?? 'Administración').' | VASCOR OPS';
$assetBaseUrl = $base;
$activeModule = 'administracion';
$activeSection = $adminSection ?? 'usuarios';
$content = $adminContent ?? '';
$moduleNavigation='<section class="admin-module-hero"><p class="eyebrow">Administración</p><h1>Administración del sistema</h1><p>Gestiona usuarios, roles, áreas, módulos, permisos y accesos.</p><nav class="admin-tabs" aria-label="Secciones de Administración">'.($can('usuarios.ver')?'<a class="'.($activeSection==='usuarios'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=usuarios"'.($activeSection==='usuarios'?' aria-current="page"':'').'>Usuarios</a>':'').($can('roles.ver')?'<a class="'.($activeSection==='roles'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=roles"'.($activeSection==='roles'?' aria-current="page"':'').'>Roles y permisos</a>':'').($can('areas.ver')?'<a class="'.($activeSection==='areas'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=areas"'.($activeSection==='areas'?' aria-current="page"':'').'>Áreas</a>':'').($can('modulos.ver')?'<a class="'.($activeSection==='modulos'?'is-active':'').'" href="'.$base.'/index.php?modulo=administracion&amp;seccion=modulos"'.($activeSection==='modulos'?' aria-current="page"':'').'>Módulos</a>':'').'</nav></section>';
$additionalStyles = [$base.'/assets/css/admin.css',$base.'/assets/css/admin-shell.css'];
$additionalScripts = [$base.'/assets/js/admin.js'];
$user = $this->authorization->user();
$header = AuthenticatedHeaderBuilder::build($user,$base.'/index.php?modulo=auth&accion=logout',$csrfToken,['systemSubtitle'=>'Plataforma Operativa']);
require dirname(__DIR__).'/layouts/app.php';
