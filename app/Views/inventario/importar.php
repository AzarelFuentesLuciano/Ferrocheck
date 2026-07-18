<?php
require_once __DIR__ . '/../../../config/config.php';

$modulo = trim((string) ($_GET['modulo'] ?? 'dashboard'));
if ($modulo === '') {
    $modulo = 'dashboard';
}

$ferroSeccion = trim((string) ($_GET['seccion'] ?? 'consulta-vin'));
$esFerrocheck = $modulo === 'ferrocheck';

$esModulo = static function (string $id) use ($modulo): bool {
    return $modulo === $id;
};

$tituloPagina = 'VASCOR OPS';
if ($esFerrocheck) {
    $tituloPagina .= ' | FerroCheck';
} elseif ($esModulo('inventario-material')) {
    $tituloPagina .= ' | Inventario de Material';
} elseif ($esModulo('control-escaneres')) {
    $tituloPagina .= ' | Control de Escáneres';
} elseif ($esModulo('reportes')) {
    $tituloPagina .= ' | Reportes';
} elseif ($esModulo('administracion')) {
    $tituloPagina .= ' | Administración';
} elseif ($esModulo('configuracion-general')) {
    $tituloPagina .= ' | Configuración General';
} else {
    $tituloPagina .= ' | Dashboard';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vascor-design-system.css">
</head>
<body>
    <div class="dashboard-shell">
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebarNav">
                <span class="menu-toggle__icon">☰</span>
            </button>
            <div class="brand">
                <div class="brand-logo" aria-label="Logo VASCOR OPS">
                    <div class="brand-mark">
                        <span class="brand-mark__rail"></span>
                        <span class="brand-mark__rail brand-mark__rail--secondary"></span>
                        <span class="brand-mark__core">VO</span>
                    </div>
                </div>
                <div>
                    <h1>VASCOR OPS</h1>
                    <p>Plataforma Operativa</p>
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

        <div class="dashboard-body">
            <aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegación lateral">
                <div class="sidebar__section">
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=dashboard" class="sidebar__item<?php echo $esModulo('dashboard') ? ' active' : ''; ?>" data-label="Dashboard">
                        <span class="sidebar__icon">🏠</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=dashboard" class="sidebar__item sidebar__ferro-desktop<?php echo $esFerrocheck ? ' active' : ''; ?>" data-label="FerroCheck">
                        <span class="sidebar__icon">🚂</span>
                        <span class="sidebar__text">FerroCheck</span>
                    </a>

                    <details class="sidebar-group sidebar__ferro-mobile<?php echo $esFerrocheck ? ' is-open' : ''; ?>" <?php echo $esFerrocheck ? 'open' : ''; ?>>
                        <summary class="sidebar__item sidebar__item--summary<?php echo $esFerrocheck ? ' active' : ''; ?>" data-label="FerroCheck" aria-controls="ferrocheckMobileSubmenu" aria-expanded="<?php echo $esFerrocheck ? 'true' : 'false'; ?>">
                            <span class="sidebar__icon">🚂</span>
                            <span class="sidebar__text">FerroCheck</span>
                        </summary>
                        <div class="sidebar-submenu" id="ferrocheckMobileSubmenu">
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=dashboard" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'dashboard' ? ' active' : ''; ?>">Dashboard</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'consulta-vin' ? ' active' : ''; ?>">Consulta VIN</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'importar-excel' ? ' active' : ''; ?>">Importar Excel</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=busqueda-multiple" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'busqueda-multiple' ? ' active' : ''; ?>">Búsqueda múltiple</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=configuracion" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'configuracion' ? ' active' : ''; ?>">Configuración</a>
                        </div>
                    </details>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=inventario-material" class="sidebar__item<?php echo $esModulo('inventario-material') ? ' active' : ''; ?>" data-label="Inventario de Material">
                        <span class="sidebar__icon">📦</span>
                        <span class="sidebar__text">Inventario de Material</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item" data-label="Inventario de Patio">
                        <span class="sidebar__icon">🚛</span>
                        <span class="sidebar__text">Inventario de Patio</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=control-escaneres" class="sidebar__item<?php echo $esModulo('control-escaneres') ? ' active' : ''; ?>" data-label="Control de Escáneres">
                        <span class="sidebar__icon">📡</span>
                        <span class="sidebar__text">Control de Escáneres</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=reportes" class="sidebar__item<?php echo $esModulo('reportes') ? ' active' : ''; ?>" data-label="Reportes">
                        <span class="sidebar__icon">📊</span>
                        <span class="sidebar__text">Reportes</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=administracion" class="sidebar__item<?php echo $esModulo('administracion') ? ' active' : ''; ?>" data-label="Administración">
                        <span class="sidebar__icon">👤</span>
                        <span class="sidebar__text">Administración</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=configuracion-general" class="sidebar__item<?php echo $esModulo('configuracion-general') ? ' active' : ''; ?>" data-label="Configuración General">
                        <span class="sidebar__icon">⚙</span>
                        <span class="sidebar__text">Configuración General</span>
                    </a>
                </div>
            </aside>

            <main class="main-content">
                <?php if ($esModulo('dashboard')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Dashboard</p>
                        <h2>Resumen general de la operación</h2>
                        <p>Vista ejecutiva de VASCOR OPS para monitoreo rápido del estado operativo.</p>
                    </section>

                    <section class="stats-grid">
                        <article class="stat-card fade-in">
                            <div class="stat-icon">🚘</div>
                            <div>
                                <h3>Vehículos revisados hoy</h3>
                                <p class="counter">128</p>
                                <small>Turno actual</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">⏳</div>
                            <div>
                                <h3>Vehículos pendientes</h3>
                                <p class="counter">34</p>
                                <small>En cola operativa</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">📦</div>
                            <div>
                                <h3>Material registrado</h3>
                                <p class="counter">1,240</p>
                                <small>Items activos</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">📡</div>
                            <div>
                                <h3>Escáneres disponibles</h3>
                                <p class="counter">22</p>
                                <small>Unidades listas</small>
                            </div>
                        </article>
                    </section>

                    <section class="module-empty-grid">
                        <article class="panel-card module-empty">
                            <p class="eyebrow">Indicadores</p>
                            <h3>Inventarios activos</h3>
                            <p>12 inventarios con actualización dentro del SLA.</p>
                        </article>
                        <article class="panel-card module-empty">
                            <p class="eyebrow">Patio</p>
                            <h3>Ocupación Patio Norte</h3>
                            <p>74% de capacidad operativa.</p>
                        </article>
                        <article class="panel-card module-empty">
                            <p class="eyebrow">Patio</p>
                            <h3>Ocupación Patio Sur</h3>
                            <p>61% de capacidad operativa.</p>
                        </article>
                        <article class="panel-card module-empty">
                            <p class="eyebrow">Actividad</p>
                            <h3>Últimos eventos</h3>
                            <p>Sin incidencias críticas en los últimos 30 minutos.</p>
                        </article>
                    </section>
                <?php elseif ($esFerrocheck): ?>
                    <section class="vascor-module" aria-label="FerroCheck">
                        <header class="vascor-module-header">
                            <div>
                                <span class="vascor-module-header__category">Operación ferroviaria</span>
                                <h1>FerroCheck</h1>
                                <p>Consulta, validación e importación del inventario ferroviario.</p>
                            </div>
                            <span class="vascor-module-header__status"><i aria-hidden="true"></i> Operación activa</span>
                        </header>
                        <nav class="vascor-module-nav" aria-label="Secciones de FerroCheck">
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'dashboard' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=dashboard"><span aria-hidden="true">▦</span>Dashboard</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'consulta-vin' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin"><span aria-hidden="true">⌕</span>Consulta VIN</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'importar-excel' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel"><span aria-hidden="true">⇧</span>Importar Excel</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'busqueda-multiple' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=busqueda-multiple"><span aria-hidden="true">▤</span>Búsqueda múltiple</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'configuracion' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=configuracion"><span aria-hidden="true">⚙</span>Configuración</a>
                        </nav>
                    </section>

                    <?php if ($ferroSeccion === 'dashboard'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Dashboard</span><h2>Resumen de la operación ferroviaria</h2><p>Disponibilidad de servicios e indicadores principales del inventario.</p></div>
                    </div>
                    <section id="estado" class="status-banner panel-card fade-in">
                        <div class="status-banner__head">
                            <div>
                                <p class="eyebrow">FerroCheck</p>
                                <h2>Módulo operativo integrado en VASCOR OPS</h2>
                            </div>
                            <div class="status-banner__badge">Operación activa</div>
                        </div>
                        <div class="status-list">
                            <div class="status-item">
                                <div>
                                    <strong>Importación</strong>
                                    <span>Disponible</span>
                                </div>
                                <span class="status-dot status-dot-success"></span>
                            </div>
                            <div class="status-item">
                                <div>
                                    <strong>Verificador</strong>
                                    <span>Disponible</span>
                                </div>
                                <span class="status-dot status-dot-success"></span>
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

                    <?php elseif ($ferroSeccion === 'importar-excel'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Importar Excel</span><h2>Actualización de inventario</h2><p>Carga y valida el archivo operativo de Ferromex.</p></div>
                    </div>

                    <section id="importador" class="panel-card accordion-card fade-in">
                        <button class="accordion-toggle" type="button" aria-expanded="true">
                            <div class="accordion-title-wrap">
                                <p class="eyebrow">Importar Excel</p>
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

                    <?php elseif ($ferroSeccion === 'consulta-vin' || $ferroSeccion === 'busqueda-multiple'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow"><?php echo $ferroSeccion === 'consulta-vin' ? 'Consulta VIN' : 'Búsqueda múltiple'; ?></span><h2><?php echo $ferroSeccion === 'consulta-vin' ? 'Validación de plataforma' : 'Validación consolidada de plataformas'; ?></h2><p><?php echo $ferroSeccion === 'consulta-vin' ? 'Consulta el estado y ubicación de una plataforma ferroviaria.' : 'Valida varios códigos en una sola operación.'; ?></p></div>
                    </div>

                    <section id="verificacion" class="panel-card verifier-card fade-in">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">Consulta VIN / Búsqueda múltiple</p>
                                <h2>Verificador de Plataformas</h2>
                                <p>Pegue uno o varios códigos para validar su ubicación y revisar si cuentan con evidencia.</p>
                            </div>
                        </div>
                        <textarea class="verifier-textarea" placeholder="Ejemplo: TTGX985062&#10;TTGX852741"></textarea>
                        <div class="actions actions-left">
                            <button class="btn btn-primary" type="button">Verificar</button>
                        </div>
                    </section>

                    <section id="resultados" class="result-panel fade-in">
                        <div class="result-header result-header--split">
                            <div>
                                <p class="eyebrow">Resultados</p>
                                <h3>Última verificación del operador</h3>
                            </div>
                            <button class="btn btn-secondary" type="button" id="exportExcelBtn">Exportar a Excel</button>
                        </div>
                        <div class="table-wrapper">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Transportista</th>
                                        <th>Ubicación</th>
                                        <th aria-hidden="true"></th>
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
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>—</td>
                                        <td><button class="action-link" type="button">👁 Ver</button></td>
                                    </tr>
                                    <tr class="state-otra">
                                        <td>TTGX852741</td>
                                        <td>Ferromex</td>
                                        <td>Monterrey</td>
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>—</td>
                                        <td><button class="action-link" type="button">👁 Ver</button></td>
                                    </tr>
                                    <tr class="state-evidencia">
                                        <td>BNFS301330</td>
                                        <td>Kansas</td>
                                        <td>Encantada</td>
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>📷 Disponible</td>
                                        <td><button class="action-link" type="button">🖼 Ver foto</button></td>
                                    </tr>
                                    <tr class="state-no-encontrado">
                                        <td>TTGX741852</td>
                                        <td>Kansas</td>
                                        <td>Sin registro</td>
                                        <td aria-hidden="true"></td>
                                        <td>—</td>
                                        <td>❌</td>
                                        <td><button class="action-link" type="button">📷 Capturar</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <?php else: ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Configuración</span><h2>Preferencias de FerroCheck</h2><p>Parámetros visuales y operativos disponibles para el módulo.</p></div>
                    </div>
                    <section class="panel-card vascor-empty-state fade-in" aria-labelledby="ferroConfigTitle">
                        <div class="vascor-empty-state__icon" aria-hidden="true">⚙</div>
                        <h3 id="ferroConfigTitle">Configuración del módulo</h3>
                        <p>Esta sección conserva su estado actual y queda preparada para mostrar opciones autorizadas.</p>
                    </section>
                    <?php endif; ?>
                <?php elseif ($esModulo('inventario-material')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Inventario de Material</p>
                        <h2>Vista inicial del módulo</h2>
                        <p>Esta vista queda preparada para incorporar dashboard, productos, entradas, salidas, movimientos, requisiciones y alertas.</p>
                    </section>
                    <section class="panel-card module-empty fade-in">
                        <h3>Módulo listo para desarrollo</h3>
                        <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    </section>
                <?php elseif ($esModulo('control-escaneres')): ?>
                    <?php require __DIR__ . '/../control-escaneres/plantilla.php'; ?>
                <?php elseif ($esModulo('reportes')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Reportes</p>
                        <h2>Vista inicial del módulo</h2>
                        <p>Esta vista queda preparada para concentrar reportes operativos y exportaciones en futuras fases.</p>
                    </section>
                    <section class="panel-card module-empty fade-in">
                        <h3>Módulo listo para desarrollo</h3>
                        <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    </section>
                <?php elseif ($esModulo('administracion')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Administración</p>
                        <h2>Vista inicial del módulo</h2>
                        <p>Esta vista queda preparada para administrar usuarios, roles y configuración operativa.</p>
                    </section>
                    <section class="panel-card module-empty fade-in">
                        <h3>Módulo listo para desarrollo</h3>
                        <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    </section>
                <?php elseif ($esModulo('configuracion-general')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Configuración General</p>
                        <h2>Vista inicial del módulo</h2>
                        <p>Esta vista queda preparada para parámetros globales de VASCOR OPS.</p>
                    </section>
                    <section class="panel-card module-empty fade-in">
                        <h3>Módulo listo para desarrollo</h3>
                        <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    </section>
                <?php else: ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Módulo</p>
                        <h2>Vista no disponible</h2>
                        <p>Seleccione un módulo válido en el menú lateral.</p>
                    </section>
                <?php endif; ?>
            </main>
        </div>

        <footer id="footer" class="footer">
            <div class="footer__content">
                <p class="footer__title">VASCOR OPS v1.0</p>
                <p class="footer__subtitle">Plataforma Operativa</p>
                <p class="footer__label">Desarrollado por</p>
                <p class="footer__developer">Ing. Azarel Fuentes Luciano</p>
                <p class="footer__copyright">© 2026 VASCOR OPS. Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>

    <?php if ($esFerrocheck): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/importador.js"></script>
    <?php else: ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/operaciones-patio.js"></script>
    <?php endif; ?>
</body>
</html>
