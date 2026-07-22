<?php
declare(strict_types=1);

use App\Domain\ControlEscaneres\ScannerStatus;

$vistaActual = 'catalogo';
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$fields = [
    'tag' => ['TAG', 40],
    'brand' => ['Marca', 100],
    'model' => ['Modelo', 120],
    'serial' => ['Número de serie', 120],
    'imei' => ['IMEI', 15],
    'phone' => ['Teléfono', 30],
    'iccid' => ['ICCID', 32],
    'network' => ['Red u operador', 100],
    'plan' => ['Plan', 100],
    'activity' => ['Actividad habitual', 150],
    'location' => ['Ubicación', 180],
    'age' => ['Antigüedad descriptiva', 120],
];
$old = is_array($registrationForm['values'] ?? null) ? $registrationForm['values'] : [];
$errors = is_array($registrationForm['errors'] ?? null) ? $registrationForm['errors'] : [];
$value = static fn(string $field): string => $h($old[$field] ?? '');
ob_start();
?>
<div class="vascor-ui ce-catalog">
    <?php
    $pageTitle = 'Registrar escáner';
    $pageDescription = 'Alta manual en el catálogo canónico. Si omites el código se generará automáticamente.';
    $breadcrumbs = ['Control de Escáneres', 'Catálogo', 'Registrar'];
    require dirname(__DIR__) . '/components/page-header.php';
    ?>
    <?php foreach (($registrationMessages ?? []) as $message): $alertType=$message['type']??'info';$alertMessage=$message['message']??'';require dirname(__DIR__).'/components/alert.php';endforeach; ?>
    <form class="vo-card ce-form" method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=registrar">
        <input type="hidden" name="_csrf" value="<?= $h($registrationCsrfToken ?? '') ?>">
        <div class="vo-form-grid">
            <div class="vo-field">
                <label for="scanner-code">Código <span>(opcional)</span></label>
                <input class="vo-input" id="scanner-code" name="code" pattern="SC-[0-9]{4,}" maxlength="30" placeholder="SC-0001" value="<?= $value('code') ?>" <?= isset($errors['code'])?'aria-invalid="true" aria-describedby="scanner-code-error"':'' ?>>
                <?php if(isset($errors['code'])): ?><small class="vo-field__error" id="scanner-code-error"><?= $h($errors['code']) ?></small><?php endif; ?>
            </div>
            <?php foreach ($fields as $name => [$label, $maxLength]): ?>
                <div class="vo-field">
                    <label for="scanner-<?= $h($name) ?>"><?= $h($label) ?><?= in_array($name, ['brand', 'model'], true) ? ' *' : '' ?></label>
                    <input class="vo-input" id="scanner-<?= $h($name) ?>" name="<?= $h($name) ?>" maxlength="<?= $maxLength ?>" value="<?= $value($name) ?>" <?= in_array($name, ['tag','brand','model'], true) ? 'required' : '' ?> <?= in_array($name, ['imei', 'phone', 'iccid'], true) ? 'inputmode="numeric"' : '' ?> <?= $name==='imei'?'pattern="[0-9]{15}"':'' ?> <?= isset($errors[$name])?'aria-invalid="true" aria-describedby="scanner-'.$h($name).'-error"':'' ?>>
                    <?php if(isset($errors[$name])): ?><small class="vo-field__error" id="scanner-<?= $h($name) ?>-error"><?= $h($errors[$name]) ?></small><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="vo-field"><label for="scanner-area">Área habitual *</label><select class="vo-select" id="scanner-area" name="area_id" required <?= isset($errors['area_id'])?'aria-invalid="true" aria-describedby="scanner-area-error"':'' ?>><option value="">Selecciona un área</option><?php foreach(($registrationAreas??[])as$area): ?><option value="<?= (int)$area['id'] ?>" <?= (string)($old['area_id']??'')===(string)$area['id']?'selected':'' ?>><?= $h($area['nombre']) ?></option><?php endforeach; ?></select><?php if(isset($errors['area_id'])): ?><small class="vo-field__error" id="scanner-area-error"><?= $h($errors['area_id']) ?></small><?php endif; ?></div>
            <div class="vo-field"><label for="scanner-organizational-area">Área organizacional propietaria *</label><select class="vo-select" id="scanner-organizational-area" name="organizational_area_id" required <?= isset($errors['organizational_area_id'])?'aria-invalid="true" aria-describedby="scanner-organizational-area-error"':'' ?>><option value="">Selecciona el departamento responsable</option><?php foreach(($registrationOrganizationalAreas??[])as$area):?><option value="<?= (int)$area['id'] ?>" <?= (string)($old['organizational_area_id']??'')===(string)$area['id']?'selected':'' ?>><?= $h($area['nombre']) ?></option><?php endforeach?></select><small>No sustituye el área operativa habitual.</small><?php if(isset($errors['organizational_area_id'])):?><small class="vo-field__error" id="scanner-organizational-area-error"><?= $h($errors['organizational_area_id']) ?></small><?php endif?></div>
            <div class="vo-field">
                <label for="scanner-status">Estado inicial</label>
                <select class="vo-select" id="scanner-status" name="status">
                    <?php foreach (ScannerStatus::VALUES as $status): ?>
                        <option value="<?= $h($status) ?>" <?= ($old['status']??'disponible')===$status ? 'selected' : '' ?>><?= $h(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vo-field"><label for="scanner-active">Actividad</label><select class="vo-select" id="scanner-active" name="active" required><option value="1" <?= ($old['active']??'1')==='1'?'selected':'' ?>>Activo</option><option value="0" <?= ($old['active']??'1')==='0'?'selected':'' ?>>Inactivo</option></select><?php if(isset($errors['active'])): ?><small class="vo-field__error"><?= $h($errors['active']) ?></small><?php endif; ?></div>
            <div class="vo-field">
                <label for="scanner-main-photo">Fotografía principal <span>(opcional)</span></label>
                <input class="vo-input" id="scanner-main-photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment">
                <small>JPEG, PNG o WebP; máximo 5 MB.</small>
            </div>
            <div class="vo-field" style="grid-column:1/-1">
                <label for="scanner-observations">Observaciones</label>
                <textarea class="vo-textarea" id="scanner-observations" name="observations" maxlength="500"><?= $value('observations') ?></textarea>
            </div>
        </div>
        <p class="vo-muted">Si marca o modelo aún no se conocen, captura “Por definir” de forma explícita para dejar visible la advertencia operativa.</p>
        <div class="vo-actions">
            <button class="vo-btn vo-btn--primary" type="submit">Registrar escáner</button>
            <a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=catalogo">Cancelar</a>
        </div>
    </form>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>
