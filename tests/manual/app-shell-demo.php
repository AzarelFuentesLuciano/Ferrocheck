<?php

declare(strict_types=1);

/**
 * DEMO MANUAL AISLADA DEL APP SHELL.
 * No debe exponerse en producción. No usa datos reales, endpoints, sesiones,
 * controladores o base de datos y no modifica información.
 */

$pageTitle = 'VASCOR OPS | Demo aislada del App Shell';
$documentLanguage = 'es';
$assetBaseUrl = '/Ferrocheck/public';
$activeModule = 'ferrocheck';
$activeSection = 'consulta-vin';

$header = [
    'systemName' => 'VASCOR OPS',
    'systemSubtitle' => 'Plataforma Operativa · Demo',
    'versionLabel' => 'App Shell paralelo',
    'menuLabel' => 'Abrir o cerrar navegación de demostración',
];

$footer = [
    'title' => 'VASCOR OPS v1.0',
    'subtitle' => 'Demostración aislada del App Shell',
    'creditLabel' => 'Desarrollado por',
    'developer' => 'Ing. Azarel Fuentes Luciano',
    'year' => date('Y'),
];

$modules = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'url' => '#demo-dashboard', 'icon' => '⌂'],
    [
        'id' => 'ferrocheck',
        'label' => 'FerroCheck',
        'url' => '#demo-ferrocheck',
        'icon' => '▰',
        'sections' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'url' => '#demo-ferro-dashboard'],
            ['id' => 'consulta-vin', 'label' => 'Consulta VIN', 'url' => '#demo-consulta-vin'],
            ['id' => 'importar-excel', 'label' => 'Importar Excel', 'url' => '#demo-importar'],
            ['id' => 'busqueda-multiple', 'label' => 'Búsqueda múltiple', 'url' => '#demo-busqueda'],
            ['id' => 'configuracion', 'label' => 'Configuración', 'url' => '#demo-configuracion'],
        ],
    ],
    ['id' => 'inventario-material', 'label' => 'Inventario de Material', 'url' => '#demo-material', 'icon' => '□'],
    ['id' => 'inventario-patio', 'label' => 'Inventario de Patio', 'url' => '#demo-patio', 'icon' => '▣'],
    ['id' => 'control-escaneres', 'label' => 'Control de Escáneres', 'url' => '#demo-escaneres', 'icon' => '⌁'],
    ['id' => 'reportes', 'label' => 'Reportes', 'url' => '#demo-reportes', 'icon' => '▥'],
    ['id' => 'administracion', 'label' => 'Administración', 'url' => '#demo-administracion', 'icon' => '●'],
];

$moduleNavigation = <<<'HTML'
<nav class="app-shell-demo-nav" aria-label="Navegación interna ficticia">
    <a href="#demo-ferro-dashboard">Dashboard</a>
    <a class="is-active" href="#demo-consulta-vin" aria-current="page">Consulta VIN</a>
    <a href="#demo-importar">Importar Excel</a>
    <a href="#demo-busqueda">Búsqueda múltiple</a>
    <a href="#demo-configuracion">Configuración</a>
</nav>
HTML;

$content = <<<'HTML'
<style>
    .app-shell-demo { display: grid; gap: 18px; min-width: 0; }
    .app-shell-demo-hero { padding: 22px; border-radius: var(--vascor-radius-lg, 18px); color: #fff; background: linear-gradient(125deg, var(--vascor-primary-dark, #102a43), #164d75); box-shadow: var(--vascor-shadow-md); }
    .app-shell-demo-hero small { color: #80e1e8; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
    .app-shell-demo-hero h1 { margin: 5px 0; font-size: clamp(1.5rem, 3vw, 2.1rem); }
    .app-shell-demo-hero p { margin: 0; color: #d9e8f2; }
    .app-shell-demo-nav { display: flex; gap: 6px; max-width: 100%; overflow-x: auto; padding: 6px; border: 1px solid var(--vascor-border); border-radius: 14px; background: #fff; box-shadow: var(--vascor-shadow-sm); }
    .app-shell-demo-nav a { flex: 0 0 auto; padding: 9px 12px; border-radius: 9px; color: var(--vascor-text-muted); font-size: .82rem; font-weight: 500; text-decoration: none; white-space: nowrap; }
    .app-shell-demo-nav a.is-active { color: #fff; background: var(--vascor-primary-dark); }
    .app-shell-demo-heading h2 { margin: 3px 0; }
    .app-shell-demo-heading p { margin: 0; color: var(--vascor-text-muted); }
    .app-shell-demo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .app-shell-demo-card, .app-shell-demo-panel { padding: 17px; border: 1px solid var(--vascor-border); border-radius: 14px; background: #fff; box-shadow: var(--vascor-shadow-sm); }
    .app-shell-demo-card span { color: var(--vascor-text-muted); font-size: .75rem; }
    .app-shell-demo-card strong { display: block; margin-top: 6px; color: var(--vascor-primary-dark); font-size: 1.7rem; }
    .app-shell-demo-panel { overflow: hidden; }
    .app-shell-demo-panel h3 { margin-top: 0; }
    .app-shell-demo-table-wrap { max-width: 100%; overflow-x: auto; }
    .app-shell-demo-table { width: 100%; min-width: 560px; border-collapse: collapse; }
    .app-shell-demo-table th, .app-shell-demo-table td { padding: 11px; border-bottom: 1px solid var(--vascor-border); text-align: left; font-size: .8rem; }
    .app-shell-demo-table th { color: var(--vascor-text-muted); font-size: .68rem; letter-spacing: .06em; text-transform: uppercase; }
    .app-shell-demo-note { margin: 0; padding: 14px; border-left: 4px solid var(--vascor-info); border-radius: 8px; color: var(--vascor-text-muted); background: #f4f9fc; }
    @media (max-width: 1024px) { .app-shell-demo-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .app-shell-demo-grid { grid-template-columns: 1fr; } .app-shell-demo-hero { padding: 18px; } }
</style>

<section class="app-shell-demo" id="demo-consulta-vin">
    <header class="app-shell-demo-hero">
        <small>Operación ferroviaria · contenido ficticio</small>
        <h1>FerroCheck</h1>
        <p>Vista aislada para revisar el contrato visual del nuevo App Shell.</p>
    </header>

    <div class="app-shell-demo-heading">
        <small>Consulta VIN</small>
        <h2>Resumen de demostración</h2>
        <p>Los valores siguientes son ficticios y no representan información operativa.</p>
    </div>

    <div class="app-shell-demo-grid" aria-label="Indicadores ficticios">
        <article class="app-shell-demo-card"><span>Registros simulados</span><strong>128</strong></article>
        <article class="app-shell-demo-card"><span>En revisión</span><strong>14</strong></article>
        <article class="app-shell-demo-card"><span>Completados</span><strong>109</strong></article>
        <article class="app-shell-demo-card"><span>Alertas ficticias</span><strong>5</strong></article>
    </div>

    <article class="app-shell-demo-panel">
        <h3>Actividad ficticia</h3>
        <div class="app-shell-demo-table-wrap">
            <table class="app-shell-demo-table">
                <thead><tr><th>Referencia</th><th>Módulo</th><th>Estado</th><th>Actualización</th></tr></thead>
                <tbody>
                    <tr><td>DEMO-001</td><td>FerroCheck</td><td>Disponible</td><td>Hace 5 min</td></tr>
                    <tr><td>DEMO-002</td><td>Escáneres</td><td>En revisión</td><td>Hace 18 min</td></tr>
                    <tr><td>DEMO-003</td><td>Inventario</td><td>Completado</td><td>Hace 42 min</td></tr>
                </tbody>
            </table>
        </div>
    </article>

    <p class="app-shell-demo-note">Esta página solo valida estructura, responsive y accesibilidad básica del shell. No ejecuta procesos del ERP.</p>
</section>
HTML;

$additionalStyles = [];
$additionalScripts = [];

require __DIR__ . '/../../app/Views/layouts/app.php';
