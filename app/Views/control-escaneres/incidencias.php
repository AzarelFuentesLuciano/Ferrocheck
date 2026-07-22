<?php
$vistaActual = 'incidencias';
$h = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
ob_start();

$pageTitle = 'Incidencias';
$pageDescription = 'Registra hallazgos y documenta su resolución sin perder la trazabilidad del equipo.';
$breadcrumbs = ['Control de Escáneres', 'Incidencias'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation">
    <?php if (isset($integrationError)): ?>
        <?php $alertType = 'error'; $alertMessage = $integrationError; require __DIR__ . '/../components/alert.php'; ?>
    <?php elseif (!isset($incidentForm) || $incidentForm->scannerId === null): ?>
        <?php $alertType = 'info'; $alertMessage = 'Selecciona un escáner desde el catálogo para consultar o registrar incidencias.'; require __DIR__ . '/../components/alert.php'; ?>
        <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Ir al catálogo</a></div>
    <?php else: ?>
        <?php
        foreach ($incidentForm->messages as $message) {
            $alertType = ($message['type'] ?? 'info') === 'error' ? 'error' : ($message['type'] ?? 'info');
            $alertMessage = $message['message'] ?? '';
            require __DIR__ . '/../components/alert.php';
        }
        $scannerSummary = [
            'code' => $incidentForm->scannerCode,
            'description' => 'Las incidencias quedan asociadas a este equipo y, cuando corresponde, a su movimiento vigente.',
        ];
        require __DIR__ . '/../components/scanner-summary.php';
        $openIncidents = array_filter($incidentForm->incidents, static fn($incident) => !in_array($incident->status->value, ['resuelta', 'cancelada'], true));
        ?>

        <nav class="vo-operation-tabs" aria-label="Secciones de incidencias">
            <a class="vo-btn vo-btn--secondary" href="#reportar-incidencia">Reportar incidencia</a>
            <a class="vo-btn vo-btn--secondary" href="#incidencias-registradas">Incidencias registradas (<?= count($incidentForm->incidents) ?>)</a>
        </nav>

        <div class="vo-operation-layout">
            <form id="reportar-incidencia" class="vo-form-section" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= $h($incidentForm->csrfToken) ?>">
                <input type="hidden" name="scanner_id" value="<?= (int) $incidentForm->scannerId ?>">
                <input type="hidden" name="movement_id" value="<?= (int) $incidentForm->movementId ?>">
                <input type="hidden" name="operation" value="report">
                <?php $sectionTitle = 'Reportar una incidencia'; $sectionDescription = 'Describe únicamente el hallazgo observado. Podrás documentar la solución por separado.'; require __DIR__ . '/../components/section-header.php'; ?>
                <div class="ce-form-grid">
                    <div class="ce-field">
                        <label for="incident-type">Tipo de incidencia</label>
                        <input id="incident-type" class="ce-input" name="type" required autocomplete="off" aria-describedby="incident-type-help">
                        <small id="incident-type-help">Ejemplo: daño físico, falla de lectura o accesorio faltante.</small>
                    </div>
                    <div class="ce-field">
                        <label for="incident-severity">Severidad</label>
                        <select id="incident-severity" class="ce-select" name="severity">
                            <option value="baja">Baja</option><option value="media">Media</option><option value="alta">Alta</option><option value="critica">Crítica</option>
                        </select>
                        <small>Selecciona el impacto observado, sin anticipar el diagnóstico.</small>
                    </div>
                    <div class="ce-field ce-field--full">
                        <label for="incident-description">Descripción del hallazgo</label>
                        <textarea id="incident-description" class="ce-textarea" name="description" required rows="5" aria-describedby="incident-description-help"></textarea>
                        <small id="incident-description-help">No incluyas contraseñas, PIN, PUK ni otros datos sensibles.</small>
                    </div>
                    <div class="ce-field ce-field--full"><label for="incident-photos">Fotografías <span>(opcional)</span></label><input id="incident-photos" class="ce-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple><small>JPEG, PNG o WebP; máximo 5 MB por archivo.</small></div>
                </div>
                <div class="vo-actions"><button class="ce-btn ce-btn--primary" type="submit">Reportar incidencia</button></div>
            </form>
            <?php
            $operationTitle = 'Antes de reportar';
            $operationItems = ['Equipo' => $incidentForm->scannerCode, 'Incidencias abiertas' => (string) count($openIncidents)];
            $operationMessage = 'Verifica el equipo y registra hechos concretos. El reporte no cambia por sí solo el estado del escáner.';
            require __DIR__ . '/../components/operation-summary.php';
            ?>
        </div>

        <section id="incidencias-registradas" class="vo-form-section" aria-labelledby="incidents-title">
            <?php $sectionTitle = 'Incidencias registradas'; $sectionDescription = 'Consulta el estado actual y abre la resolución solamente cuando tengas una solución confirmada.'; require __DIR__ . '/../components/section-header.php'; ?>
            <?php if (!$incidentForm->incidents): ?>
                <?php $alertType = 'info'; $alertMessage = 'Este escáner no tiene incidencias registradas.'; require __DIR__ . '/../components/alert.php'; ?>
            <?php endif; ?>
            <?php foreach ($incidentForm->incidents as $incident): ?>
                <?php $isClosed = in_array($incident->status->value, ['resuelta', 'cancelada'], true); ?>
                <article class="ce-list__item">
                    <div class="ce-list__body">
                        <strong>#<?= (int) $incident->id ?> · <?= $h($incident->type) ?></strong>
                        <small>Severidad: <?= $h(ucfirst($incident->severity->value)) ?> · Estado: <?= $h(ucfirst($incident->status->value)) ?></small>
                        <?php foreach ($incidentForm->followUps[$incident->id] ?? [] as $followUp): ?><p><small><?= $h($followUp['created_at']) ?> · <?= $h(str_replace('_', ' ', $followUp['estado_nuevo'])) ?>: <?= $h($followUp['comentario']) ?></small></p><?php endforeach; ?>
                    </div>
                    <?php if (!$isClosed): ?>
                        <details><summary class="vo-btn vo-btn--secondary">Seguimiento y severidad</summary><div class="ce-form vo-critical-confirm">
                            <form class="ce-form" method="post"><input type="hidden" name="_csrf" value="<?= $h($incidentForm->csrfToken) ?>"><input type="hidden" name="scanner_id" value="<?= (int) $incidentForm->scannerId ?>"><input type="hidden" name="incident_id" value="<?= (int) $incident->id ?>"><input type="hidden" name="operation" value="follow_up"><label>Nota de seguimiento<textarea class="ce-textarea" name="comment" maxlength="2000" required></textarea></label><button class="ce-btn ce-btn--primary" type="submit">Guardar seguimiento</button></form>
                            <form class="ce-form" method="post"><input type="hidden" name="_csrf" value="<?= $h($incidentForm->csrfToken) ?>"><input type="hidden" name="scanner_id" value="<?= (int) $incidentForm->scannerId ?>"><input type="hidden" name="incident_id" value="<?= (int) $incident->id ?>"><input type="hidden" name="operation" value="severity"><label>Nueva severidad<select class="ce-select" name="severity" required><?php foreach (['baja','media','alta','critica'] as $severity): ?><option value="<?= $severity ?>"<?= $severity === $incident->severity->value ? ' selected' : '' ?>><?= $h(ucfirst($severity)) ?></option><?php endforeach; ?></select></label><label>Motivo<textarea class="ce-textarea" name="reason" maxlength="2000" required></textarea></label><button class="ce-btn ce-btn--primary" type="submit">Cambiar severidad</button></form>
                            <form class="ce-form" method="post"><input type="hidden" name="_csrf" value="<?= $h($incidentForm->csrfToken) ?>"><input type="hidden" name="scanner_id" value="<?= (int) $incidentForm->scannerId ?>"><input type="hidden" name="incident_id" value="<?= (int) $incident->id ?>"><input type="hidden" name="operation" value="cancel"><label>Motivo de cancelación<textarea class="ce-textarea" name="reason" maxlength="2000" required></textarea></label><button class="ce-btn" type="submit">Cancelar incidencia</button></form>
                        </div></details>
                        <details>
                            <summary class="vo-btn vo-btn--secondary">Documentar resolución</summary>
                            <form class="ce-form vo-critical-confirm" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="_csrf" value="<?= $h($incidentForm->csrfToken) ?>">
                                <input type="hidden" name="scanner_id" value="<?= (int) $incidentForm->scannerId ?>">
                                <input type="hidden" name="incident_id" value="<?= (int) $incident->id ?>">
                                <input type="hidden" name="operation" value="resolve">
                                <div class="ce-field">
                                    <label for="resolution-<?= (int) $incident->id ?>">Resolución aplicada</label>
                                    <input id="resolution-<?= (int) $incident->id ?>" class="ce-input" name="resolution" required autocomplete="off">
                                </div>
                                <div class="ce-field">
                                    <label for="resulting-status-<?= (int) $incident->id ?>">Estado resultante del equipo</label>
                                    <select id="resulting-status-<?= (int) $incident->id ?>" class="ce-select" name="resulting_status">
                                        <?php foreach ($incidentForm->allowedStatuses as $status): ?><option value="<?= $h($status) ?>"><?= $h(ucwords(str_replace('_', ' ', $status))) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <p>Esta acción cerrará la incidencia y registrará el estado seleccionado.</p>
                                <div class="ce-field"><label for="resolution-photos-<?= (int) $incident->id ?>">Evidencias de resolución <span>(opcional)</span></label><input id="resolution-photos-<?= (int) $incident->id ?>" class="ce-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple></div>
                                <button class="ce-btn ce-btn--primary" type="submit">Confirmar resolución</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
