<?php
$vistaActual = 'expediente';
$h = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$humanize = static function ($value): string {
    $labels = ['create' => 'Registro creado', 'update' => 'Información actualizada', 'success' => 'Completado', 'failed' => 'No completado'];
    $key = strtolower((string) $value);
    return $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $value));
};
ob_start();
$pageTitle = 'Expediente del escáner';
$pageDescription = 'Consulta la identidad, el estado y la trazabilidad operativa del equipo en un solo lugar.';
$breadcrumbs = ['Control de Escáneres', 'Expediente'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation vo-history">
<?php if (isset($integrationError)): ?>
    <?php $alertType = 'error'; $alertMessage = $integrationError; require __DIR__ . '/../components/alert.php'; ?>
<?php elseif (!isset($historyViewModel)): ?>
    <?php $alertType = 'info'; $alertMessage = 'Selecciona un escáner desde el catálogo para abrir su expediente.'; require __DIR__ . '/../components/alert.php'; ?>
    <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= $h(($basePath ?? '') . '/control-escaneres/catalogo') ?>">Ir al catálogo</a></div>
<?php else: $scanner = $historyViewModel->scanner; ?>
    <?php foreach ($historyViewModel->messages as $message): $alertType = $message['type'] ?? 'info'; $alertMessage = $message['message'] ?? ''; require __DIR__ . '/../components/alert.php'; endforeach; ?>
    <?php $scannerSummary = ['code' => $scanner['code'], 'status' => $scanner['status'], 'description' => ($scanner['brand'] ?? '') . ' · ' . ($scanner['model'] ?? '') . ' · Serie ' . ($scanner['serial'] ?? '—')]; require __DIR__ . '/../components/scanner-summary.php'; ?>

    <nav class="vo-operation-tabs" aria-label="Secciones del expediente"><a class="vo-btn vo-btn--secondary" href="#resumen">Resumen</a><a class="vo-btn vo-btn--secondary" href="#trazabilidad">Trazabilidad</a><a class="vo-btn vo-btn--secondary" href="#actividad">Actividad relacionada</a><a class="vo-btn vo-btn--secondary" href="#auditoria">Auditoría</a></nav>

    <section id="resumen" class="vo-form-section">
        <?php $sectionTitle = 'Identificación y estado'; $sectionDescription = 'Datos operativos presentados de forma segura para reconocer el equipo.'; require __DIR__ . '/../components/section-header.php'; ?>
        <div class="ce-detail-grid">
        <?php foreach (['Código QR' => $scanner['qr'], 'Marca' => $scanner['brand'], 'Modelo' => $scanner['model'], 'Número de serie' => $scanner['serial'], 'IMEI protegido' => $scanner['imei'], 'Teléfono protegido' => $scanner['phone'], 'ICCID protegido' => $scanner['iccid'], 'Conservación' => $scanner['conservation'] ?? '—', 'Activo' => $scanner['active'] ? 'Sí' : 'No'] as $label => $value): ?>
            <div class="ce-detail"><small><?= $h($label) ?></small><strong><?= $h($value) ?></strong></div>
        <?php endforeach; ?>
        </div>
    </section>

    <section id="trazabilidad" class="vo-form-section">
        <?php $sectionTitle = 'Trazabilidad principal'; $sectionDescription = 'Secuencia cronológica consolidada por el sistema para este equipo.'; require __DIR__ . '/../components/section-header.php'; ?>
        <?php if (!$historyViewModel->timeline): $alertType = 'info'; $alertMessage = 'Aún no hay actividad operativa para mostrar.'; require __DIR__ . '/../components/alert.php'; else: ?>
            <div class="vo-timeline"><?php foreach ($historyViewModel->timeline as $event): $timelineEntry = ['at' => $event['at'], 'title' => $humanize($event['type']), 'description' => 'Actividad registrada en el expediente del equipo.']; require __DIR__ . '/../components/timeline-entry.php'; endforeach; ?></div>
        <?php endif; ?>
    </section>

    <section id="actividad" class="vo-history-grid">
        <article class="vo-form-section"><?php $sectionTitle = 'Movimientos'; $sectionDescription = count($historyViewModel->movements) . ' registro(s)'; require __DIR__ . '/../components/section-header.php'; ?><?php foreach ($historyViewModel->movements as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($item['folio']) ?> · <?= $h($humanize($item['status'])) ?></strong><small>Responsable: <?= $h($item['custodian']) ?> · Entrega: <?= $h($item['deliveredAt']) ?></small></div></div><?php endforeach; ?><?php if (!$historyViewModel->movements): ?><p class="vo-muted">Sin movimientos registrados.</p><?php endif; ?></article>
        <article class="vo-form-section"><?php $sectionTitle = 'Inspecciones'; $sectionDescription = count($historyViewModel->inspections) . ' registro(s)'; require __DIR__ . '/../components/section-header.php'; ?><?php foreach ($historyViewModel->inspections as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['type'])) ?> · Calificación <?= $h($item['rating'] ?? '—') ?></strong><small>Batería <?= $h($item['battery'] ?? '—') ?>% · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?><?php if (!$historyViewModel->inspections): ?><p class="vo-muted">Sin inspecciones registradas.</p><?php endif; ?></article>
        <article class="vo-form-section"><?php $sectionTitle = 'Incidencias'; $sectionDescription = count($historyViewModel->incidents) . ' registro(s)'; require __DIR__ . '/../components/section-header.php'; ?><?php foreach ($historyViewModel->incidents as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($item['type']) ?> · Severidad <?= $h($humanize($item['severity'])) ?></strong><small><?= $h($humanize($item['status'])) ?> · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?><?php if (!$historyViewModel->incidents): ?><p class="vo-muted">Sin incidencias registradas.</p><?php endif; ?></article>
        <article class="vo-form-section"><?php $sectionTitle = 'Evidencias'; $sectionDescription = count($historyViewModel->evidences) . ' registro(s)'; require __DIR__ . '/../components/section-header.php'; ?><?php foreach ($historyViewModel->evidences as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['type'])) ?></strong><small>Capturada: <?= $h($item['capturedAt']) ?></small></div></div><?php endforeach; ?><?php if (!$historyViewModel->evidences): ?><p class="vo-muted">Sin evidencias registradas.</p><?php endif; ?></article>
    </section>

    <section id="auditoria" class="vo-form-section">
        <?php $sectionTitle = 'Auditoría visible'; $sectionDescription = 'Acciones y resultados relevantes expresados en lenguaje operativo.'; require __DIR__ . '/../components/section-header.php'; ?>
        <?php foreach ($historyViewModel->auditEvents as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['action'])) ?></strong><small>Resultado: <?= $h($humanize($item['result'])) ?> · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?>
        <?php if (!$historyViewModel->auditEvents): ?><p class="vo-muted">Sin eventos de auditoría visibles.</p><?php endif; ?>
    </section>
<?php endif; ?>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
