<?php
require_once __DIR__ . '/../../../config/config.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>VASCOR OPS | Inventario de Patio</title>
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
    <script src="<?php echo $escape(BASE_URL); ?>/assets/js/pwa.js?v=5" data-service-worker-url="<?php echo $escape(BASE_URL); ?>/service-worker.js" data-pwa-base-url="<?php echo $escape(BASE_URL); ?>/" defer></script>
    <script src="<?php echo $escape(BASE_URL); ?>/assets/js/pwa-install-footer.js?v=5" defer></script>
</body>
</html>
