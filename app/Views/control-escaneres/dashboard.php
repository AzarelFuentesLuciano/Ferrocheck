<?php
$vistaActual = 'dashboard';
ob_start();
$pageTitle = 'Estado de la operación';
$pageDescription = 'Orientación operativa para localizar equipos y continuar con la acción correspondiente.';
$breadcrumbs = ['Control de Escáneres', 'Inicio'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation">
    <?php $alertType = 'info'; $alertMessage = 'Los indicadores ejecutivos estarán disponibles cuando exista una fuente de datos consolidada y validada.'; require __DIR__ . '/../components/alert.php'; ?>
    <section class="vo-form-section" aria-labelledby="dashboard-start">
        <?php $sectionTitle = '¿Qué necesitas hacer?'; $sectionDescription = 'Comienza por localizar el equipo. Desde su ficha podrás acceder a las operaciones autorizadas.'; require __DIR__ . '/../components/section-header.php'; ?>
        <div class="ce-grid ce-grid--2">
            <article class="vo-card">
                <h3 id="dashboard-start">Consultar un escáner</h3>
                <p>Busca por código o revisa el catálogo para conocer el estado actual y abrir su expediente.</p>
                <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= htmlspecialchars(BASE_URL . '/index.php?modulo=control-escaneres&seccion=catalogo', ENT_QUOTES, 'UTF-8') ?>">Abrir catálogo</a></div>
            </article>
            <article class="vo-card">
                <h3>Revisar la operación</h3>
                <p>Consulta el historial general cuando la integración de actividad consolidada esté disponible.</p>
                <div class="vo-actions"><a class="vo-btn vo-btn--secondary" href="<?= htmlspecialchars(BASE_URL . '/index.php?modulo=control-escaneres&seccion=historial', ENT_QUOTES, 'UTF-8') ?>">Ver historial provisional</a></div>
            </article>
        </div>
    </section>
    <section class="vo-form-section">
        <?php $sectionTitle = 'Flujo recomendado'; $sectionDescription = 'Una secuencia simple para mantener la trazabilidad.'; require __DIR__ . '/../components/section-header.php'; ?>
        <?php $processSteps = ['Localiza el equipo', 'Revisa su estado', 'Elige la operación', 'Confirma la información']; $currentStep = 1; require __DIR__ . '/../components/process-steps.php'; ?>
        <p class="vo-muted">Las opciones de entrega, recepción, incidencias y mantenimiento se habilitan según el equipo seleccionado y su estado.</p>
    </section>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
