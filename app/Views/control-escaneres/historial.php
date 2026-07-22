<?php
$vistaActual = 'historial';
ob_start();
$pageTitle = 'Historial operativo';
$pageDescription = 'Punto de consulta para la actividad consolidada de los escáneres.';
$breadcrumbs = ['Control de Escáneres', 'Historial'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation vo-history">
    <?php $alertType = 'info'; $alertMessage = 'Vista provisional: el historial general todavía no está conectado a una fuente de datos consolidada.'; require __DIR__ . '/../components/alert.php'; ?>
    <section class="vo-form-section">
        <?php $sectionTitle = 'Actividad consolidada'; $sectionDescription = 'Cuando la integración esté disponible, aquí podrás consultar entregas, recepciones, mantenimiento y cambios de estado.'; require __DIR__ . '/../components/section-header.php'; ?>
        <div class="vo-empty-panel">
            <h3>Sin actividad disponible</h3>
            <p>No se muestran registros de ejemplo para evitar confundir información simulada con operaciones reales.</p>
            <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Consultar expediente por escáner</a></div>
        </div>
    </section>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
