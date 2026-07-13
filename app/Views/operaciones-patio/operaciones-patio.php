<?php
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroCheck - Operaciones de Patio</title>
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
                    <div class="info-panel__item info-panel__item--active">Módulo Operaciones de Patio</div>
                </div>
            </div>
        </header>

        <div class="dashboard-body">
            <aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegación lateral">
                <div class="sidebar__section">
                    <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar__item" data-label="Dashboard">
                        <span class="sidebar__icon">⌂</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item active" data-label="Operaciones de Patio">
                        <span class="sidebar__icon">⚙</span>
                        <span class="sidebar__text">Operaciones de Patio</span>
                    </a>
                </div>
            </aside>

            <main class="main-content">
                <section class="panel-card fade-in">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Nuevo módulo</p>
                            <h2><?php echo htmlspecialchars($contexto['modulo'] ?? 'Operaciones de Patio', ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p>
                                Arquitectura base integrada correctamente. Este módulo está preparado para desarrollo incremental
                                de próximas fases sin afectar funcionalidades existentes.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="panel-card fade-in">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Estado</p>
                            <h2>Integración MVC completada</h2>
                            <p>
                                Estado del módulo: <?php echo htmlspecialchars(($contexto['estado']['phase'] ?? 'Base'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
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

    <script src="<?php echo BASE_URL; ?>/assets/js/operaciones-patio.js"></script>
</body>
</html>
