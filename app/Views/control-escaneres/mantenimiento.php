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
        <div class="vo-actions"><a class="vo-btn vo-btn--primary" href="<?= $h($basePath ?? '') ?>/control-escaneres/catalogo">Ir al catálogo</a></div>
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
            <form class="vo-form-section" method="post">
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
    <?php endif; ?>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
