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
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin" class="sidebar-submenu__item<?php echo $esFerrocheck && ($ferroSeccion === 'consulta-vin' || $ferroSeccion === 'busqueda-multiple') ? ' active' : ''; ?>">Buscar Plataformas</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel" class="sidebar-submenu__item<?php echo $esFerrocheck && $ferroSeccion === 'importar-excel' ? ' active' : ''; ?>">Importar Excel</a>
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
                    <?php require __DIR__ . '/partials/ferrocheck-content.php'; ?>
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
