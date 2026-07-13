<?php
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroCheck Preview 1.0</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
</head>
<body>
<div class="global-loader" id="globalLoader" aria-hidden="true">
    <div class="global-loader__card">
        <div class="global-loader__spinner"></div>
        <p data-loader-text>Procesando...</p>
    </div>
</div>
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
<div class="app-shell">
    <div class="sidebar-backdrop" aria-hidden="true"></div>
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebarNav">☰</button>
            <div class="brand-block">
                <div class="brand-logo">FC</div>
                <div>
                    <h1>FerroCheck</h1>
                    <p>Preview 1.0 · Plataforma Empresarial</p>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="meta-chip">Fecha: <strong id="currentDate">--</strong></div>
            <div class="meta-chip">Hora: <strong id="currentTime">--:--:--</strong></div>
        </div>
    </header>

    <div class="ticker-bar">
        <div class="ticker-track">
            <span>Preview 1.0 estable</span>
            <span>Importador oficial Ferromex</span>
            <span>Dashboard en vivo</span>
            <span>Operaciones de Patio UI</span>
            <span>Preparado para IA, OCR, Immich, API y Android</span>
        </div>
    </div>

    <div class="layout">
        <aside class="sidebar" id="sidebarNav">
            <a class="sidebar__item active" href="#dashboard">Dashboard</a>
            <a class="sidebar__item" href="#importador">Importador</a>
            <a class="sidebar__item" href="#verificador">Verificador</a>
            <a class="sidebar__item" href="#resultados">Resultados</a>
            <a class="sidebar__item" href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio">Operaciones de Patio</a>
            <a class="sidebar__item" href="#futuro">Futuro</a>
        </aside>

        <main class="content">
            <section id="dashboard" class="panel panel-highlight">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Dashboard</p>
                        <h2>Monitoreo ejecutivo</h2>
                        <p>Panel de control para operación diaria de plataformas y evidencias.</p>
                    </div>
                    <div class="last-import-card">
                        <span>Ultima importacion</span>
                        <strong id="lastImportValue">Sin registros</strong>
                    </div>
                </div>
                <div class="kpi-grid">
                    <article class="kpi-card">
                        <h3>Cantidad de registros</h3>
                        <p id="kpiCantidadRegistros">0</p>
                    </article>
                    <article class="kpi-card">
                        <h3>Total plataformas</h3>
                        <p id="kpiTotalPlataformas">0</p>
                    </article>
                    <article class="kpi-card">
                        <h3>Ferromex</h3>
                        <p id="kpiTotalFerromex">0</p>
                    </article>
                    <article class="kpi-card">
                        <h3>Kansas</h3>
                        <p id="kpiTotalKansas">0</p>
                    </article>
                    <article class="kpi-card">
                        <h3>Estado servidor</h3>
                        <p id="kpiServidor">En linea</p>
                    </article>
                    <article class="kpi-card">
                        <h3>Estado base de datos</h3>
                        <p id="kpiBaseDatos">Conectada</p>
                    </article>
                </div>
            </section>

            <section id="importador" class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Importador</p>
                        <h2>Importador oficial Ferromex</h2>
                        <p>Soporta UTF-8, ISO-8859-1, Windows-1252, mojibake y variantes de encabezados.</p>
                    </div>
                </div>

                <form action="<?php echo BASE_URL; ?>/index.php" method="post" enctype="multipart/form-data" class="importador-form">
                    <label class="dropzone" id="dropzone" for="fileInput">
                        <input type="file" id="fileInput" name="archivo" accept=".xlsx,.xls" hidden>
                        <div class="dropzone-inner">
                            <h3>Arrastra el Excel oficial o selecciona archivo</h3>
                            <p>Formatos permitidos: .xlsx y .xls</p>
                        </div>
                    </label>

                    <div class="file-info" id="fileInfo" hidden>
                        <div><span>Archivo</span><strong id="fileName">-</strong></div>
                        <div><span>Tamano</span><strong id="fileSize">-</strong></div>
                        <div><span>Tipo</span><strong id="fileType">-</strong></div>
                        <div><span>Registros</span><strong id="recordCount">0</strong></div>
                        <div><span>Estado</span><strong id="fileStatus">Pendiente</strong></div>
                    </div>

                    <div class="progress-block">
                        <div class="progress-labels">
                            <span>Progreso</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" id="progressFill"></div>
                        </div>
                        <p class="status-message status-info" id="statusMessage">Listo para analizar archivo</p>
                    </div>

                    <div class="actions">
                        <label class="btn btn-secondary" for="fileInput">Seleccionar archivo</label>
                        <button class="btn btn-primary" id="importBtn" type="button" disabled>Importar inventario</button>
                    </div>
                </form>
            </section>

            <section id="verificador" class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Verificador</p>
                        <h2>Verificador de plataformas</h2>
                        <p>Ingresa uno o varios codigos para validar su ubicacion y estado.</p>
                    </div>
                </div>
                <textarea id="verifierTextarea" class="verifier-textarea" placeholder="TTGX985062&#10;TTGX852741"></textarea>
                <div class="actions actions-left">
                    <button id="verifyBtn" class="btn btn-primary" type="button">Verificar</button>
                </div>
            </section>

            <section id="resultados" class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Resultados</p>
                        <h2>Ultima verificacion</h2>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="results-table">
                        <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Transportista</th>
                            <th>Ubicacion</th>
                            <th>Estado</th>
                            <th>Ultima actualizacion</th>
                            <th>Evidencia</th>
                            <th>Accion</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr><td colspan="7">No hay resultados para mostrar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="futuro" class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Futuro</p>
                        <h2>Componentes preparados para siguientes etapas</h2>
                    </div>
                </div>
                <div class="future-grid">
                    <article class="future-card">IA</article>
                    <article class="future-card">Immich</article>
                    <article class="future-card">OCR</article>
                    <article class="future-card">Reconocimiento automatico</article>
                    <article class="future-card">Usuarios</article>
                    <article class="future-card">Roles</article>
                    <article class="future-card">API</article>
                    <article class="future-card">App Android</article>
                </div>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>FerroCheck Preview 1.0 · Sistema empresarial en desarrollo</p>
    </footer>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/importador.js"></script>
</body>
</html>
