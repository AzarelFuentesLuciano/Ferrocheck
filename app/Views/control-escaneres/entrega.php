<?php
$vistaActual = 'entrega';
$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
ob_start();
?>
<div class="vascor-ui ce-operation">
<?php $pageTitle = 'Nueva entrega'; $pageDescription = 'Asigna el equipo y documenta sus condiciones de salida.'; $breadcrumbs = ['Control de Escáneres', 'Entrega']; require dirname(__DIR__) . '/components/page-header.php'; ?>
<?php $finderMode='entrega';$finderButtonLabel='Escanear QR para entregar';$finderTitle='Localizar equipo para entrega';require __DIR__.'/partials/equipment-finder.php'; ?>

<?php
$deliverySucceeded = false;

if (isset($deliveryForm)) {
    foreach ($deliveryForm->messages as $message) {
        if (($message['type'] ?? null) === 'success') {
            $deliverySucceeded = true;
        }

        $alertType = $message['type'] ?? 'info';
        $alertMessage = $message['message'] ?? '';
        require dirname(__DIR__) . '/components/alert.php';
    }
}
?>

<?php if (isset($integrationError)): $alertType = 'error'; $alertMessage = $integrationError; require dirname(__DIR__) . '/components/alert.php'; ?>
<?php elseif (!isset($deliveryForm) || $deliveryForm->scannerId === null):
    $emptyTitle = $deliverySucceeded
        ? 'Entrega finalizada correctamente'
        : 'Selecciona un escáner disponible';

    $emptyDescription = $deliverySucceeded
        ? 'El equipo quedó registrado como entregado. Puedes regresar al catálogo o escanear otro equipo.'
        : 'Vuelve al catálogo y elige Entrega.';

    $emptyActionUrl = BASE_URL . '/index.php?modulo=control-escaneres&seccion=catalogo';
    $emptyActionLabel = 'Volver al catálogo';
    require dirname(__DIR__) . '/components/empty-state.php';
?>
<?php else: ?>
<form class="ce-form" method="post" enctype="multipart/form-data" data-vo-operation-form>
<input type="hidden" name="_csrf" value="<?= $h($deliveryForm->csrfToken) ?>"><input type="hidden" name="scanner_id" value="<?= $deliveryForm->scannerId ?>">
<section class="vo-form-section"><h2>Custodia</h2><div class="vo-form-grid">
<?php foreach ([['person_name','delivery-name','Persona que recibe',160],['employee_number','delivery-employee','Número de empleado',50],['area','delivery-area','Área',120],['supervisor','delivery-supervisor','Supervisor',160],['responsible_name','delivery-responsible','Quien entrega',160],['shift','delivery-shift','Turno',50]] as [$name,$id,$label,$max]): ?><div class="vo-field"><label for="<?= $id ?>"><?= $label ?></label><input class="vo-input" id="<?= $id ?>" name="<?= $name ?>" maxlength="<?= $max ?>" required></div><?php endforeach; ?>
</div></section>
<section class="vo-form-section"><h2>Inspección de salida</h2><div class="vo-form-grid">
<div class="vo-field"><label for="delivery-battery">Batería (%)</label><input class="vo-input" id="delivery-battery" type="number" min="0" max="100" name="battery"></div>
<fieldset class="vo-field ce-star-field"><legend>Valoración</legend><div class="ce-stars" aria-label="Valoración de 1 a 5 estrellas"><?php foreach (range(5, 1) as $star): ?><input id="delivery-rating-<?= $star ?>" type="radio" name="rating" value="<?= $star ?>"><label for="delivery-rating-<?= $star ?>" title="<?= $star ?> estrellas">★</label><?php endforeach; ?></div><small>Se almacena normalizada de 0 a 100.</small></fieldset>
<?php foreach ($deliveryForm->components as $component): ?><div class="vo-field"><label for="delivery-<?= $h($component) ?>"><?= $h(ucwords(str_replace('_', ' ', $component))) ?></label><select class="vo-select" id="delivery-<?= $h($component) ?>" name="component[<?= $h($component) ?>]" required><option value="">Selecciona</option><option>excelente</option><option>bueno</option><option>regular</option><option>dañado</option><option>no funciona</option><option>faltante</option></select></div><?php endforeach; ?>
<div class="vo-field" style="grid-column:1/-1"><label for="delivery-observations">Observaciones</label><textarea class="vo-textarea" id="delivery-observations" name="observations" maxlength="1000"></textarea></div></div></section>
<?php $signaturePrefix = 'delivery'; $signatureFirstLabel = 'Firma de quien recibe'; $signatureSecondLabel = 'Firma de quien entrega'; require __DIR__ . '/partials/evidence-fields.php'; ?>
<div class="vo-actions"><button class="vo-btn vo-btn--primary" type="submit" data-loading-label="Registrando entrega…">Registrar entrega</button><a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Cancelar</a></div>
</form><?php endif; ?></div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
