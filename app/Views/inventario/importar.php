<?php
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroCheck Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
</head>
<body>
    <div class="dashboard-shell">
        <div class="sidebar-backdrop" aria-hidden="true"></div>
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebarNav">
                <span class="menu-toggle__icon">☰</span>
            </button>
            <div class="brand">
                <div class="brand-logo" aria-label="Logo FerroCheck">
                    <div class="brand-mark">
                        <span class="brand-mark__rail"></span>
                        <span class="brand-mark__rail brand-mark__rail--secondary"></span>
                        <span class="brand-mark__core">FC</span>
                    </div>
                </div>
                <div>
                    <h1>FerroCheck</h1>
                    <p>Sistema Inteligente de Verificación Ferroviaria</p>
                </div>
            </div>
            <div class="topbar-meta">
                <div class="info-panel" aria-live="polite">
                    <div class="info-panel__item info-panel__item--active" data-role="version">Versión v1.0</div>
                    <div class="info-panel__item" data-role="date"><span class="meta-label">Fecha</span><span id="currentDate">--</span></div>
                    <div class="info-panel__item" data-role="time"><span class="meta-label">Hora</span><span id="currentTime">--:--:--</span></div>
                </div>
            </div>
        </header>

        <div class="ticker-bar" aria-label="Indicadores del sistema">
            <div class="ticker-track">
                <div class="ticker-group">
                    <span>◆ Versión v1.0</span>
                    <span>◆ Servidor en línea</span>
                    <span>◆ Ferromex actualizado</span>
                    <span>◆ Kansas disponible</span>
                    <span>◆ Última actualización</span>
                    <span>◆ Fecha completa</span>
                    <span>◆ Hora completa</span>
                </div>
                <div class="ticker-group" aria-hidden="true">
                    <span>◆ Versión v1.0</span>
                    <span>◆ Servidor en línea</span>
                    <span>◆ Ferromex actualizado</span>
                    <span>◆ Kansas disponible</span>
                    <span>◆ Última actualización</span>
                    <span>◆ Fecha completa</span>
                    <span>◆ Hora completa</span>
                </div>
            </div>
        </div>

        <div class="dashboard-body">
            <aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegación lateral">
                <div class="sidebar__section">
                    <a href="#estado" class="sidebar__item active" data-label="Dashboard">
                        <span class="sidebar__icon">⌂</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>
                    <a href="#importador" class="sidebar__item" data-label="Importador">
                        <span class="sidebar__icon">⬇</span>
                        <span class="sidebar__text">Importador</span>
                    </a>
                    <a href="#verificacion" class="sidebar__item" data-label="Verificación">
                        <span class="sidebar__icon">⌕</span>
                        <span class="sidebar__text">Verificación</span>
                    </a>
                    <a href="#resultados" class="sidebar__item" data-label="Resultados">
                        <span class="sidebar__icon">📋</span>
                        <span class="sidebar__text">Resultados</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item" data-label="Operaciones de Patio">
                        <span class="sidebar__icon">⚙</span>
                        <span class="sidebar__text">Operaciones de Patio</span>
                    </a>
                </div>
            </aside>
            <main class="main-content">
                <section id="estado" class="status-banner panel-card fade-in">
                    <div class="status-banner__head">
                        <div>
                            <p class="eyebrow">Resumen del Sistema</p>
                            <h2>Operación continua y supervisión activa</h2>
                        </div>
                        <div class="status-banner__badge">Monitoreo en vivo</div>
                    </div>
                    <div class="status-list">
                        <div class="status-item">
                            <div>
                                <strong>Inventario Ferromex</strong>
                                <span>Actualizado</span>
                            </div>
                            <span class="status-dot"></span>
                        </div>
                        <div class="status-item">
                            <div>
                                <strong>Kansas</strong>
                                <span>Disponible</span>
                            </div>
                            <span class="status-dot status-dot-warning"></span>
                        </div>
                        <div class="status-item">
                            <div>
                                <strong>Servidor</strong>
                                <span>En línea</span>
                            </div>
                            <span class="status-dot status-dot-success"></span>
                        </div>
                    </div>
                    <div class="status-banner__footer">
                        <span>Última actualización</span>
                        <strong>11/07/2026 18:45</strong>
                    </div>
                </section>

                <section id="indicadores" class="stats-grid">
                    <article class="stat-card fade-in">
                        <div class="stat-icon">🚆</div>
                        <div>
                            <h3>Inventario Ferromex</h3>
                            <p class="counter">0</p>
                            <small>Registros</small>
                        </div>
                    </article>
                    <article class="stat-card fade-in">
                        <div class="stat-icon">📍</div>
                        <div>
                            <h3>En Encantada</h3>
                            <p class="counter">0</p>
                            <small>Plataformas</small>
                        </div>
                    </article>
                    <article class="stat-card fade-in">
                        <div class="stat-icon">🧭</div>
                        <div>
                            <h3>Otra ubicación</h3>
                            <p class="counter">0</p>
                            <small>Registros</small>
                        </div>
                    </article>
                    <article class="stat-card fade-in">
                        <div class="stat-icon">⚠️</div>
                        <div>
                            <h3>No encontrados</h3>
                            <p class="counter">0</p>
                            <small>Faltantes</small>
                        </div>
                    </article>
                </section>

                <section id="importador" class="panel-card accordion-card fade-in">
                    <button class="accordion-toggle" type="button" aria-expanded="true">
                        <div class="accordion-title-wrap">
                            <p class="eyebrow">Importador</p>
                            <h2>Importador Ferromex</h2>
                            <p>Importa el inventario de forma ordenada, segura y profesional.</p>
                        </div>
                        <span class="accordion-icon">▾</span>
                    </button>

                    <div class="accordion-content">
                        <form action="<?php echo BASE_URL; ?>/index.php" method="post" enctype="multipart/form-data" class="importador-form">
                            <label class="dropzone" for="fileInput" id="dropzone">
                                <input type="file" id="fileInput" name="archivo" accept=".csv,.xlsx,.xls" hidden>
                                <div class="dropzone-content">
                                    <div class="dropzone-icon">📄</div>
                                    <h2>Arrastra y suelta tu archivo aquí</h2>
                                    <p>Archivos compatibles: .xlsx y .xls</p>
                                </div>
                            </label>

                            <div class="file-info" id="fileInfo" hidden>
                                <div class="file-info-item">
                                    <span class="label">Archivo seleccionado:</span>
                                    <span class="value" id="fileName">-</span>
                                </div>
                                <div class="file-info-item">
                                    <span class="label">Tamaño:</span>
                                    <span class="value" id="fileSize">-</span>
                                </div>
                                <div class="file-info-item">
                                    <span class="label">Tipo:</span>
                                    <span class="value" id="fileType">-</span>
                                </div>
                                <div class="file-info-item">
                                    <span class="label">Registros detectados:</span>
                                    <span class="value" id="recordCount">0</span>
                                </div>
                                <div class="file-info-item">
                                    <span class="label">Condición del archivo:</span>
                                    <span class="value" id="fileStatus">-</span>
                                </div>
                            </div>

                            <div class="progress-block" aria-live="polite">
                                <div class="progress-labels">
                                    <span>Progreso</span>
                                    <span id="progressPercent">0%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" id="progressFill"></div>
                                </div>
                                <p class="status-message" id="statusMessage">Listo para importar</p>
                            </div>

                            <div class="actions">
                                <label class="btn btn-secondary" for="fileInput">Seleccionar archivo</label>
                                <button class="btn btn-primary" id="importBtn" type="button" disabled>Importar Inventario</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section id="verificacion" class="panel-card verifier-card fade-in">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Verificación</p>
                            <h2>Verificador de Plataformas</h2>
                            <p>Pegue uno o varios códigos para validar su ubicación y revisar si cuentan con evidencia.</p>
                        </div>
                    </div>
                    <textarea class="verifier-textarea" placeholder="Ejemplo: TTGX985062
TTGX852741"></textarea>
                    <div class="actions actions-left">
                        <button class="btn btn-primary" type="button">Verificar</button>
                    </div>
                </section>

                <section id="resultados" class="result-panel fade-in">
                    <div class="result-header">
                        <div>
                            <p class="eyebrow">Resultados</p>
                            <h3>Última verificación del operador</h3>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Transportista</th>
                                    <th>Ubicación</th>
                                    <th>Última actualización</th>
                                    <th>Evidencia</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="state-encantada">
                                    <td>TTGX985062</td>
                                    <td>Ferromex</td>
                                    <td>Encantada</td>
                                    <td>11/07/2026</td>
                                    <td>—</td>
                                    <td><button class="action-link" type="button">👁 Ver</button></td>
                                </tr>
                                <tr class="state-otra">
                                    <td>TTGX852741</td>
                                    <td>Ferromex</td>
                                    <td>Monterrey</td>
                                    <td>11/07/2026</td>
                                    <td>—</td>
                                    <td><button class="action-link" type="button">👁 Ver</button></td>
                                </tr>
                                <tr class="state-evidencia">
                                    <td>BNFS301330</td>
                                    <td>Kansas</td>
                                    <td>Encantada</td>
                                    <td>11/07/2026</td>
                                    <td>📷 Disponible</td>
                                    <td><button class="action-link" type="button">🖼 Ver foto</button></td>
                                </tr>
                                <tr class="state-no-encontrado">
                                    <td>TTGX741852</td>
                                    <td>Kansas</td>
                                    <td>Sin registro</td>
                                    <td>—</td>
                                    <td>❌</td>
                                    <td><button class="action-link" type="button">📷 Capturar</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>

        <footer id="footer" class="footer">
            <div class="footer__content">
                <p class="footer__title">FerroCheck v1.0</p>
                <p class="footer__subtitle">Sistema Inteligente de Verificación Ferroviaria</p>
                <p class="footer__label">Desarrollado por</p>
                <p class="footer__developer">Ing. Azarel Fuentes Luciano</p>
                <p class="footer__copyright">© 2026 FerroCheck. Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/importador.js"></script>
</body>
</html>
