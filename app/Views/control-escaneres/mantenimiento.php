<?php
$vistaActual = 'mantenimiento';
$h = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
ob_start();
$pageTitle = 'Mantenimiento';
$pageDescription = 'Documenta la salida al taller o el regreso del equipo mediante transiciones autorizadas.';
$breadcrumbs = ['Control de Escáneres', 'Mantenimiento'];
require __DIR__ . '/../components/page-header.php';
?>
<div class="ce-operation">
    <?php if (isset($integrationError)): ?>
        <?php $alertType = 'error'; $alertMessage = $integrationError; require __DIR__ . '/../components/alert.php'; ?>
    <?php elseif (!isset($maintenanceForm) || $maintenanceForm->scannerId === null): ?>
        <?php $alertType = 'info'; $alertMessage = 'Selecciona un escáner desde el catálogo para consultar las acciones de mantenimiento disponibles.'; require __DIR__ . '/../components/alert.php'; ?>
        <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Ir al catálogo</a></div>
    <?php else: ?>
        <?php
        foreach ($maintenanceForm->messages as $message) {
            $alertType = ($message['type'] ?? 'info') === 'error' ? 'error' : ($message['type'] ?? 'info');
            $alertMessage = $message['message'] ?? '';
            require __DIR__ . '/../components/alert.php';
        }
        $isReturning = $maintenanceForm->status === 'mantenimiento';
        $scannerSummary = [
            'code' => $maintenanceForm->scannerCode,
            'status' => $maintenanceForm->status,
            'description' => $isReturning ? 'El equipo está en mantenimiento y puede documentarse su regreso.' : 'El equipo puede enviarse a mantenimiento si requiere intervención técnica.',
        ];
        require __DIR__ . '/../components/scanner-summary.php';
        ?>
        <div class="vo-operation-layout">
            <form class="vo-form-section" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= $h($maintenanceForm->csrfToken) ?>">
                <input type="hidden" name="scanner_id" value="<?= (int) $maintenanceForm->scannerId ?>">
                <input type="hidden" name="operation" value="<?= $isReturning ? 'return' : 'send' ?>">
                <?php
                $sectionTitle = $isReturning ? 'Registrar regreso de mantenimiento' : 'Enviar a mantenimiento';
                $sectionDescription = $isReturning ? 'Documenta el resultado del servicio y el estado en el que regresa el equipo.' : 'Explica el motivo técnico antes de confirmar la salida del equipo.';
                require __DIR__ . '/../components/section-header.php';
                ?>
                <div class="ce-form-grid">
                    <div class="ce-field ce-field--full">
                        <label for="maintenance-reason"><?= $isReturning ? 'Resultado del mantenimiento' : 'Motivo del envío' ?></label>
                        <input id="maintenance-reason" class="ce-input" name="reason" required autocomplete="off" aria-describedby="maintenance-reason-help">
                        <small id="maintenance-reason-help"><?= $isReturning ? 'Resume la intervención o diagnóstico realizado.' : 'Describe la falla o revisión que requiere el equipo.' ?></small>
                    </div>
                    <div class="ce-field">
                        <label for="maintenance-status">Estado resultante</label>
                        <select id="maintenance-status" class="ce-select" name="resulting_status">
                            <?php foreach ($maintenanceForm->allowedStatuses as $status): ?><option value="<?= $h($status) ?>"><?= $h(ucwords(str_replace('_', ' ', $status))) ?></option><?php endforeach; ?>
                        </select>
                        <small><?= $isReturning ? 'Selecciona el estado operativo verificado al regreso.' : 'Elige el estado autorizado para esta transición.' ?></small>
                    </div>
                    <div class="ce-field">
                        <label for="maintenance-observations">Observaciones <span aria-hidden="true">(opcional)</span></label>
                        <textarea id="maintenance-observations" class="ce-textarea" name="observations" rows="4"></textarea>
                        <small>No incluyas contraseñas, PIN, PUK ni otros datos sensibles.</small>
                    </div>
                    <div class="ce-field"><label for="maintenance-technician">Proveedor o técnico <span>(opcional)</span></label><input id="maintenance-technician" class="ce-input" name="technician" maxlength="160"></div>
                    <div class="ce-field"><label for="maintenance-diagnosis">Diagnóstico <span>(opcional)</span></label><textarea id="maintenance-diagnosis" class="ce-textarea" name="diagnosis" maxlength="2000"></textarea></div>
                    <div class="ce-field"><label for="maintenance-cost">Costo <span>(opcional)</span></label><input id="maintenance-cost" class="ce-input" name="cost" type="number" min="0" step="0.01"></div>
                    <div class="ce-field"><label for="maintenance-estimated">Fecha estimada <span>(opcional)</span></label><input id="maintenance-estimated" class="ce-input" name="estimated_date" type="date"></div>
                    <?php if ($isReturning): ?><div class="ce-field ce-field--full"><label for="maintenance-result">Resultado final</label><textarea id="maintenance-result" class="ce-textarea" name="result" maxlength="2000" required></textarea></div><?php endif; ?>
                    <div class="ce-field ce-field--full"><label for="maintenance-photos">Fotografías <span>(opcional)</span></label><input id="maintenance-photos" class="ce-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple><small>JPEG, PNG o WebP; máximo 5 MB por archivo.</small></div>
                </div>
                <div class="vo-critical-confirm">
                    <strong>Revisa antes de continuar</strong>
                    <p><?= $isReturning ? 'Se registrará el regreso del equipo y su nuevo estado.' : 'Se registrará la salida del equipo a mantenimiento.' ?></p>
                </div>
                <div class="vo-actions"><button class="ce-btn ce-btn--primary" type="submit"><?= $isReturning ? 'Confirmar regreso' : 'Confirmar envío' ?></button></div>
            </form>
            <?php
            $operationTitle = 'Resumen de la operación';
            $operationItems = ['Equipo' => $maintenanceForm->scannerCode, 'Estado actual' => ucwords(str_replace('_', ' ', $maintenanceForm->status)), 'Acción' => $isReturning ? 'Regresar de mantenimiento' : 'Enviar a mantenimiento'];
            $operationMessage = 'La operación conservará la trazabilidad del equipo. Confirma solamente después de revisar la información.';
            require __DIR__ . '/../components/operation-summary.php';
            ?>
        </div>
        <section class="vo-form-section"><h2>Historial de mantenimiento</h2><?php foreach ($maintenanceForm->history as $record): ?><div class="ce-list__item"><div class="ce-list__body"><strong><?= $h(ucwords($record['estado'])) ?> · <?= $h($record['motivo']) ?></strong><small>Proveedor/técnico: <?= $h($record['tecnico_proveedor'] ?? '—') ?> · Diagnóstico: <?= $h($record['diagnostico'] ?? '—') ?> · Inicio: <?= $h($record['iniciado_at']) ?> · Fin: <?= $h($record['finalizado_at'] ?? 'Pendiente') ?> · Resultado: <?= $h($record['resultado'] ?? '—') ?></small></div></div><?php endforeach; ?><?php if ($maintenanceForm->history === []): ?><p>Sin mantenimientos registrados.</p><?php endif; ?></section>
    <?php endif; ?>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
