<?php
declare(strict_types=1);

$vistaActual = 'expediente';
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$humanize = static fn(mixed $value): string => ucwords(str_replace(['_', '-'], ' ', (string) $value));
$url = static fn(string $section, int $scannerId): string => BASE_URL . '/index.php?modulo=control-escaneres&amp;seccion=' . $section . '&amp;scanner_id=' . $scannerId;
ob_start();
$pageTitle = 'Expediente integral del equipo';
$pageDescription = 'Identidad, estado, movimientos, inspecciones, evidencias y auditoría en un solo lugar.';
$breadcrumbs = ['Control de Escáneres', 'Expediente'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation vo-history">
<?php if (isset($integrationError)): ?>
    <?php $alertType = 'error'; $alertMessage = $integrationError; require __DIR__ . '/../components/alert.php'; ?>
<?php elseif (!isset($historyViewModel)): ?>
    <?php $alertType = 'info'; $alertMessage = 'Selecciona un escáner desde el catálogo para abrir su expediente.'; require __DIR__ . '/../components/alert.php'; ?>
    <a class="vo-btn vo-btn--primary" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Ir al catálogo</a>
<?php else: $scanner = $historyViewModel->scanner; $scannerId = (int) $scanner['id']; ?>
    <?php foreach ($historyViewModel->messages as $message): $alertType = $message['type'] ?? 'info'; $alertMessage = $message['message'] ?? ''; require __DIR__ . '/../components/alert.php'; endforeach; ?>
    <?php $scannerSummary = ['code' => $scanner['code'], 'status' => $scanner['status'], 'description' => ($scanner['brand'] ?? '') . ' · ' . ($scanner['model'] ?? '') . ' · TAG ' . ($scanner['tag'] ?? '—')]; require __DIR__ . '/../components/scanner-summary.php'; ?>

    <div class="vo-actions">
        <?php if ($scanner['active'] && $scanner['status'] === 'disponible'): ?><a class="vo-btn vo-btn--primary" href="<?= $url('entrega', $scannerId) ?>">Entregar</a><?php endif; ?>
        <?php if ($scanner['active'] && $scanner['status'] === 'entregado'): ?><a class="vo-btn vo-btn--primary" href="<?= $url('recepcion', $scannerId) ?>">Recibir</a><?php endif; ?>
        <?php if ($scanner['active']): ?><a class="vo-btn vo-btn--subtle" href="<?= $url('incidencias', $scannerId) ?>">Reportar incidencia</a><a class="vo-btn vo-btn--subtle" href="<?= $url('mantenimiento', $scannerId) ?>">Mantenimiento</a><a class="vo-btn vo-btn--subtle" href="<?= $url('editar', $scannerId) ?>">Editar</a><?php endif; ?>
        <a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $scannerId ?>&amp;size=700" target="_blank" rel="noopener">Imprimir QR</a>
        <a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Ir al catálogo</a>
    </div>

    <nav class="vo-operation-tabs" aria-label="Secciones del expediente"><a href="#resumen">Resumen</a><a href="#trazabilidad">Trazabilidad</a><a href="#actividad">Actividad</a><a href="#evidencias">Evidencias</a><a href="#auditoria">Auditoría</a></nav>

    <section id="resumen" class="vo-form-section">
        <?php $sectionTitle = 'Identificación y estado'; $sectionDescription = 'Los identificadores sensibles se muestran enmascarados.'; require __DIR__ . '/../components/section-header.php'; ?>
        <div class="ce-detail-grid">
            <div class="ce-detail ce-detail--qr"><a href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $scannerId ?>&amp;size=700" target="_blank" rel="noopener"><img src="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $scannerId ?>&amp;size=240" width="160" height="160" alt="QR de <?= $h($scanner['code']) ?>"></a><strong><?= $h($scanner['code']) ?></strong><small>TAG <?= $h($scanner['tag'] ?? '—') ?></small></div>
            <?php foreach (['Marca' => $scanner['brand'], 'Modelo' => $scanner['model'], 'Número de serie' => $scanner['serial'], 'IMEI protegido' => $scanner['imei'], 'Teléfono protegido' => $scanner['phone'], 'ICCID protegido' => $scanner['iccid'], 'Red' => $scanner['network'] ?? '—', 'Plan' => $scanner['plan'] ?? '—', 'Actividad habitual' => $scanner['activity'] ?? '—', 'Área organizacional propietaria' => $scanner['organizational_area'] ?? 'Sin asignar', 'Área operativa habitual' => $scanner['area'] ?? '—', 'Ubicación' => $scanner['location'] ?? '—', 'Antigüedad' => $scanner['age'] ?? '—', 'Conservación' => $scanner['conservation'] ?? '—', 'Activo' => $scanner['active'] ? 'Sí' : 'No', 'Observaciones' => $scanner['observations'] ?? '—'] as $label => $value): ?>
                <div class="ce-detail"><small><?= $h($label) ?></small><strong><?= $h($value ?: '—') ?></strong></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="trazabilidad" class="vo-form-section">
        <?php $sectionTitle = 'Trazabilidad principal'; $sectionDescription = 'Secuencia cronológica de movimientos, inspecciones, estados, incidencias y mantenimiento.'; require __DIR__ . '/../components/section-header.php'; ?>
        <?php if ($historyViewModel->timeline === []): ?><p class="vo-muted">Aún no hay actividad operativa.</p><?php else: ?><div class="vo-timeline"><?php foreach ($historyViewModel->timeline as $event): $timelineEntry = ['at' => $event['at'], 'title' => $humanize($event['type']), 'description' => 'Actividad registrada en el expediente.']; require __DIR__ . '/../components/timeline-entry.php'; endforeach; ?></div><?php endif; ?>
    </section>

    <section id="actividad" class="vo-history-grid">
        <article class="vo-form-section"><h2>Movimientos</h2><?php foreach ($historyViewModel->movements as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($item['folio']) ?> · <?= $h($humanize($item['status'])) ?></strong><small>Responsable: <?= $h($item['custodian']) ?> · Entrega: <?= $h($item['deliveredAt']) ?> · Recepción: <?= $h($item['receivedAt'] ?? 'Pendiente') ?></small><a href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=pdf&amp;movement_id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener">Ver comprobante PDF</a></div></div><?php endforeach; ?><?php if ($historyViewModel->movements === []): ?><p class="vo-muted">Sin movimientos registrados.</p><?php endif; ?></article>
        <article class="vo-form-section"><h2>Inspecciones</h2><?php foreach ($historyViewModel->inspections as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['type'])) ?> · <?= $item['rating'] === null ? 'Sin valoración' : $h($item['ratingStars']) . ' de 5 estrellas (' . $h($item['ratingTen']) . '/10)' ?></strong><small>Batería <?= $h($item['battery'] ?? '—') ?>% · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?><?php if ($historyViewModel->inspections === []): ?><p class="vo-muted">Sin inspecciones registradas.</p><?php endif; ?></article>
        <article class="vo-form-section"><h2>Incidencias</h2><?php foreach ($historyViewModel->incidents as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['type'])) ?> · Severidad <?= $h($humanize($item['severity'])) ?></strong><small><?= $h($humanize($item['status'])) ?> · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?><?php if ($historyViewModel->incidents === []): ?><p class="vo-muted">Sin incidencias registradas.</p><?php endif; ?></article>
    </section>

    <section class="vo-history-grid">
        <article class="vo-form-section"><h2>Comparaciones de recepción</h2><?php foreach ($historyViewModel->differences as $difference): ?><div class="vo-comparison__item vo-comparison__item--<?= $h($difference['clasificacion']) ?>"><strong><?= $h($humanize($difference['componente'])) ?></strong><span><?= $h($difference['valor_anterior'] ?? '—') ?> → <?= $h($difference['valor_nuevo'] ?? '—') ?> · <?= $h($humanize($difference['clasificacion'])) ?><?= (int) $difference['requiere_revision'] === 1 ? ' · revisión humana' : '' ?></span></div><?php endforeach; ?><?php if ($historyViewModel->differences === []): ?><p class="vo-muted">Sin comparaciones registradas.</p><?php endif; ?></article>
        <article class="vo-form-section"><h2>Mantenimientos</h2><?php foreach ($historyViewModel->maintenance as $maintenance): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($maintenance['estado'])) ?> · <?= $h($maintenance['motivo']) ?></strong><small>Técnico/proveedor: <?= $h($maintenance['tecnico_proveedor'] ?? '—') ?> · Diagnóstico: <?= $h($maintenance['diagnostico'] ?? '—') ?> · Costo: <?= $maintenance['costo'] === null ? '—' : '$' . $h(number_format((float) $maintenance['costo'], 2)) ?> · Inicio: <?= $h($maintenance['iniciado_at']) ?> · Fin: <?= $h($maintenance['finalizado_at'] ?? 'Pendiente') ?> · Resultado: <?= $h($maintenance['resultado'] ?? '—') ?></small></div></div><?php endforeach; ?><?php if ($historyViewModel->maintenance === []): ?><p class="vo-muted">Sin mantenimientos registrados.</p><?php endif; ?></article>
        <article class="vo-form-section"><h2>Seguimiento de incidencias</h2><?php foreach ($historyViewModel->incidentFollowUps as $followUp): ?><div class="ce-list__item"><div class="ce-list__body"><strong>#<?= (int) $followUp['incidencia_id'] ?> · <?= $h($humanize($followUp['estado_nuevo'])) ?></strong><small><?= $h($followUp['comentario']) ?> · <?= $h($followUp['created_at']) ?></small></div></div><?php endforeach; ?><?php if ($historyViewModel->incidentFollowUps === []): ?><p class="vo-muted">Sin seguimientos registrados.</p><?php endif; ?></article>
    </section>

    <section id="evidencias" class="vo-form-section">
        <h2>Fotografías y firmas</h2>
        <div class="ce-evidence-grid">
            <?php foreach ($historyViewModel->evidences as $item): ?>
                <figure class="vo-card"><a href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=evidencia&amp;evidence_id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener"><img src="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=evidencia&amp;evidence_id=<?= (int) $item['id'] ?>" loading="lazy" alt="<?= $h($humanize($item['type'])) ?>"></a><figcaption><strong><?= $h($humanize($item['type'])) ?></strong><small><?= $h($item['capturedAt']) ?></small></figcaption></figure>
            <?php endforeach; ?>
        </div>
        <?php if ($historyViewModel->evidences === []): ?><p class="vo-muted">Sin evidencias registradas.</p><?php endif; ?>
    </section>

    <section id="auditoria" class="vo-form-section"><h2>Auditoría visible</h2><?php foreach ($historyViewModel->auditEvents as $item): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h($humanize($item['action'])) ?></strong><small>Resultado: <?= $h($humanize($item['result'])) ?> · <?= $h($item['at']) ?></small></div></div><?php endforeach; ?><?php if ($historyViewModel->auditEvents === []): ?><p class="vo-muted">Sin eventos visibles.</p><?php endif; ?></section>
<?php endif; ?>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
