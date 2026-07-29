<?php
require_once __DIR__ . '/../../../config/config.php';

$modulo = trim((string) ($_GET['modulo'] ?? 'dashboard'));
if ($modulo === '') {
    $modulo = 'dashboard';
}

$ferroSeccion = trim((string) ($_GET['seccion'] ?? 'consulta-vin'));
$esFerrocheck = $modulo === 'ferrocheck';
$escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

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
    <meta name="theme-color" content="#ffffff">
    <title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="manifest" href="<?php echo $escape(BASE_URL); ?>/manifest.webmanifest?v=5">
    <link rel="apple-touch-icon" href="<?php echo $escape(BASE_URL); ?>/assets/icons/vascor-ops-minimal-v4-192.png">
    <link rel="icon" type="image/png" href="<?php echo $escape(BASE_URL); ?>/assets/icons/vascor-ops-minimal-v4-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app-shell.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vascor-design-system.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/shell-coherence.css">
    <link rel="stylesheet" href="<?php echo $escape(BASE_URL); ?>/assets/css/pwa.css?v=5">
    <link rel="stylesheet" href="<?php echo $escape(BASE_URL); ?>/assets/css/pwa-install-footer.css?v=5">
    <link rel="stylesheet" href="<?php echo $escape(BASE_URL); ?>/assets/css/pwa-update.css?v=5">
</head>
<body>
    <div class="pwa-splash" data-pwa-splash role="status" aria-label="Iniciando VASCOR OPS"><strong><span>VASCOR</span><span>OPS</span></strong></div>
    <div class="dashboard-shell">
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <?php require __DIR__ . '/../partials/header.php'; ?>

        <div class="dashboard-body">
            <?php require __DIR__ . '/../partials/legacy-sidebar.php'; ?>

            <main class="main-content">
                <?php if ($esModulo('dashboard')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Dashboard</p>
                        <h2>Resumen general de la operación</h2>
                        <p>Vista ejecutiva de VASCOR OPS para monitoreo rápido del estado operativo.</p>
                        <p class="demo-data-label">Datos de demostración</p>
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
                        <h3>Módulo en preparación</h3>
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
                        <h3>Módulo en preparación</h3>
                        <p>Sin lógica en Fase 1. Solo estructura visual y navegación.</p>
                    </section>
                <?php elseif ($esModulo('administracion')): ?>
                    <section class="panel-card fade-in module-page-header">
                        <p class="eyebrow">Administración</p>
                        <h2>Vista inicial del módulo</h2>
                        <p>Esta vista queda preparada para administrar usuarios, roles y configuración operativa.</p>
                    </section>
                    <section class="panel-card module-empty fade-in">
                        <h3>Módulo en preparación</h3>
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
    <script src="<?php echo $escape(BASE_URL); ?>/assets/js/pwa.js?v=5" data-service-worker-url="<?php echo $escape(BASE_URL); ?>/service-worker.js" data-pwa-base-url="<?php echo $escape(BASE_URL); ?>/" defer></script>
    <script src="<?php echo $escape(BASE_URL); ?>/assets/js/pwa-install-footer.js?v=5" defer></script>
</body>
</html>
