<?php
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroCheck · Operaciones de Patio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
</head>
<body>
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
<div class="app-shell">
    <div class="sidebar-backdrop" aria-hidden="true"></div>
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebarNav">☰</button>
            <div class="brand-block">
                <div class="brand-logo">FC</div>
                <div>
                    <h1>Operaciones de Patio</h1>
                    <p>Preview operativo visual</p>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/index.php">Volver a Dashboard</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar" id="sidebarNav">
            <a class="sidebar__item" href="<?php echo BASE_URL; ?>/index.php">Dashboard</a>
            <a class="sidebar__item active" href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio">Operaciones de Patio</a>
            <a class="sidebar__item" href="#norte">Norte</a>
            <a class="sidebar__item" href="#sur">Sur</a>
            <a class="sidebar__item" href="#futuro">Integraciones futuras</a>
        </aside>

        <main class="content patio-content">
            <section class="panel panel-highlight">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Mapa del Patio</p>
                        <h2>Vista operacional en tiempo real (Preview)</h2>
                    </div>
                </div>
                <div class="state-legend">
                    <span class="legend-item legend-libre">Libre</span>
                    <span class="legend-item legend-ocupada">Ocupada</span>
                    <span class="legend-item legend-facturada">Facturada</span>
                    <span class="legend-item legend-cancelada">Cancelada</span>
                    <span class="legend-item legend-evidencia">Con evidencia</span>
                    <span class="legend-item legend-sin-evidencia">Sin evidencia</span>
                    <span class="legend-item legend-verificada">Verificada</span>
                    <span class="legend-item legend-pendiente">Pendiente</span>
                </div>
            </section>

            <section class="yard-layout-grid">
                <article class="panel" id="norte">
                    <div class="panel-header compact">
                        <h3>Norte · Patio 1</h3>
                    </div>
                    <div class="yard-grid">
                        <button class="yard-platform state-libre" data-zone="Norte" data-name="Patio 1 · P1" data-state="libre" data-evidence="0">P1</button>
                        <button class="yard-platform state-ocupada" data-zone="Norte" data-name="Patio 1 · P2" data-state="ocupada" data-evidence="0">P2</button>
                        <button class="yard-platform state-facturada" data-zone="Norte" data-name="Patio 1 · P3" data-state="facturada" data-evidence="1">P3</button>
                        <button class="yard-platform state-pendiente" data-zone="Norte" data-name="Patio 1 · P4" data-state="pendiente" data-evidence="0">P4</button>
                    </div>
                </article>

                <article class="panel" id="sur">
                    <div class="panel-header compact">
                        <h3>Sur · Patio 2</h3>
                    </div>
                    <div class="yard-grid">
                        <button class="yard-platform state-verificada" data-zone="Sur" data-name="Patio 2 · S1" data-state="verificada" data-evidence="1">S1</button>
                        <button class="yard-platform state-cancelada" data-zone="Sur" data-name="Patio 2 · S2" data-state="cancelada" data-evidence="0">S2</button>
                        <button class="yard-platform state-ocupada" data-zone="Sur" data-name="Patio 2 · S3" data-state="ocupada" data-evidence="0">S3</button>
                        <button class="yard-platform state-sin-evidencia" data-zone="Sur" data-name="Patio 2 · S4" data-state="sin_evidencia" data-evidence="0">S4</button>
                    </div>
                </article>

                <aside class="panel patio-side-panel is-empty" id="patioDetailPanel">
                    <div class="panel-header compact">
                        <h3 id="detailPlatformTitle">Selecciona una plataforma</h3>
                        <p id="detailPlatformState">Sin seleccion</p>
                    </div>

                    <label class="form-label" for="detailObservation">Observaciones</label>
                    <textarea id="detailObservation" class="verifier-textarea" placeholder="Escribe observaciones de operacion, hallazgos y riesgos..."></textarea>

                    <div class="evidence-preview" id="evidencePreview">Sin evidencia cargada</div>

                    <div class="actions actions-column">
                        <button id="btnGuardarOperacion" class="btn btn-primary" type="button">Guardar</button>
                        <button id="btnCerrarOperacion" class="btn btn-secondary" type="button">Cerrar operacion</button>
                        <button id="btnExportarPdf" class="btn btn-secondary" type="button">Exportar PDF</button>
                        <button id="btnCancelarPlataforma" class="btn btn-danger" type="button">Cancelar plataforma</button>
                    </div>
                </aside>
            </section>

            <section id="futuro" class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Futuro</p>
                        <h2>Preparado para evolucion tecnica</h2>
                    </div>
                </div>
                <div class="future-grid">
                    <article class="future-card">IA operacional</article>
                    <article class="future-card">OCR de evidencias</article>
                    <article class="future-card">Sincronizacion Immich</article>
                    <article class="future-card">Roles y permisos</article>
                    <article class="future-card">API externa</article>
                    <article class="future-card">App Android de patio</article>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/operaciones-patio.js"></script>
</body>
</html>
