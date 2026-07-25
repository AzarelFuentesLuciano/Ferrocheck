<?php
require_once __DIR__ . '/../../../config/config.php';
$permisosSesion = is_array($_SESSION['auth_permissions'] ?? null) ? $_SESSION['auth_permissions'] : [];
$puedeAdministrar = in_array('administracion.acceder', $permisosSesion, true);
$modulosAutorizados = is_array($_SESSION['auth_module_keys'] ?? null) ? $_SESSION['auth_module_keys'] : [];
$puedeVerModulo = static fn(string $clave): bool => $modulosAutorizados === [] || in_array($clave, $modulosAutorizados, true);
$escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app-shell.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vascor-design-system.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/shell-coherence.css">
</head>
<body>
    <div class="dashboard-shell">
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <?php require __DIR__ . '/../partials/header.php'; ?>

        <div class="dashboard-body">
            <aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegación lateral">
                <div class="sidebar__section">
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=dashboard" class="sidebar__item" data-label="Dashboard" <?php echo $puedeVerModulo('dashboard')?'':'hidden'; ?>>
                        <span class="sidebar__icon">🏠</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>

                    <details class="sidebar-group" <?php echo $puedeVerModulo('ferrocheck')?'':'hidden'; ?>>
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

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=inventario-material" class="sidebar__item" data-label="Inventario de Material" <?php echo $puedeVerModulo('inventario_material')?'':'hidden'; ?>>
                        <span class="sidebar__icon">📦</span>
                        <span class="sidebar__text">Inventario de Material</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item active" data-label="Inventario de Patio" <?php echo $puedeVerModulo('inventario_patio')?'':'hidden'; ?>>
                        <span class="sidebar__icon">🚛</span>
                        <span class="sidebar__text">Inventario de Patio</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=control-escaneres" class="sidebar__item" data-label="Control de Escáneres" <?php echo $puedeVerModulo('control_escaneres')?'':'hidden'; ?>>
                        <span class="sidebar__icon">📡</span>
                        <span class="sidebar__text">Control de Escáneres</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=reportes" class="sidebar__item" data-label="Reportes" <?php echo $puedeVerModulo('reportes')?'':'hidden'; ?>>
                        <span class="sidebar__icon">📊</span>
                        <span class="sidebar__text">Reportes</span>
                    </a>

                    <?php if ($puedeAdministrar && $puedeVerModulo('administracion')): ?><a href="<?php echo BASE_URL; ?>/index.php?modulo=administracion" class="sidebar__item" data-label="Administración">
                        <span class="sidebar__icon">👤</span>
                        <span class="sidebar__text">Administración</span>
                    </a><?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=configuracion-general" class="sidebar__item" data-label="Configuración General" <?php echo $puedeVerModulo('configuracion_general')?'':'hidden'; ?>>
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
