<?php
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VASCOR OPS | Inventario de Patio</title>
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
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=dashboard" class="sidebar__item" data-label="Dashboard">
                        <span class="sidebar__icon">🏠</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>

                    <details class="sidebar-group">
                        <summary class="sidebar__item sidebar__item--summary" data-label="FerroCheck">
                            <span class="sidebar__icon">🚂</span>
                            <span class="sidebar__text">FerroCheck</span>
                        </summary>
                        <div class="sidebar-submenu">
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin" class="sidebar-submenu__item">Buscar Plataformas</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel" class="sidebar-submenu__item">Importar Excel</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=configuracion" class="sidebar-submenu__item">Configuración</a>
                        </div>
                    </details>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=inventario-material" class="sidebar__item" data-label="Inventario de Material">
                        <span class="sidebar__icon">📦</span>
                        <span class="sidebar__text">Inventario de Material</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item active" data-label="Inventario de Patio">
                        <span class="sidebar__icon">🚛</span>
                        <span class="sidebar__text">Inventario de Patio</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=control-escaneres" class="sidebar__item" data-label="Control de Escáneres">
                        <span class="sidebar__icon">📡</span>
                        <span class="sidebar__text">Control de Escáneres</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=reportes" class="sidebar__item" data-label="Reportes">
                        <span class="sidebar__icon">📊</span>
                        <span class="sidebar__text">Reportes</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=administracion" class="sidebar__item" data-label="Administración">
                        <span class="sidebar__icon">👤</span>
                        <span class="sidebar__text">Administración</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=configuracion-general" class="sidebar__item" data-label="Configuración General">
                        <span class="sidebar__icon">⚙</span>
                        <span class="sidebar__text">Configuración General</span>
                    </a>
                </div>
            </aside>

            <main class="main-content">
                <section class="panel-card fade-in module-page-header">
                    <p class="eyebrow">Inventario de Patio</p>
                    <h2>Vista inicial del módulo</h2>
                    <p>Este módulo queda preparado para diseñar el mapa operativo del patio en siguientes fases.</p>
                </section>

                <section class="panel-card module-empty fade-in">
                    <h3>Módulo listo para desarrollo</h3>
                    <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    <p>Estado del módulo: <?php echo htmlspecialchars(($contexto['estado']['phase'] ?? 'Base'), ENT_QUOTES, 'UTF-8'); ?></p>
                </section>
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

    <script src="<?php echo BASE_URL; ?>/assets/js/operaciones-patio.js"></script>
</body>
</html>
