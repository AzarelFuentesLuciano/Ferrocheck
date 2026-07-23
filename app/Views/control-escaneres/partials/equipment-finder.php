<?php
declare(strict_types=1);
$finderMode=$finderMode??'automatico';$finderButtonLabel=$finderButtonLabel??'Buscar equipo';$finderTitle=$finderTitle??'Localizar escáner';$finderId='equipment-finder-'.preg_replace('/[^a-z0-9_-]/','',(string)$finderMode);$finderEndpoint=BASE_URL.'/index.php?modulo=control-escaneres&accion=resolver-equipo';$qrEndpoint=BASE_URL.'/index.php?modulo=control-escaneres&accion=resolver-qr&formato=json';
?>
<div class="ce-equipment-finder" data-equipment-finder data-qr-scanner data-mode="<?= htmlspecialchars($finderMode,ENT_QUOTES,'UTF-8') ?>" data-endpoint="<?= htmlspecialchars($finderEndpoint,ENT_QUOTES,'UTF-8') ?>" data-qr-endpoint="<?= htmlspecialchars($qrEndpoint,ENT_QUOTES,'UTF-8') ?>">
 <button class="vo-btn vo-btn--subtle" type="button" data-equipment-finder-open><?= htmlspecialchars($finderButtonLabel,ENT_QUOTES,'UTF-8') ?></button>
 <dialog class="vo-card ce-equipment-finder__dialog" aria-labelledby="<?= $finderId ?>-title" data-equipment-finder-dialog>
  <h2 id="<?= $finderId ?>-title"><?= htmlspecialchars($finderTitle,ENT_QUOTES,'UTF-8') ?></h2><p>Escanea el QR o escribe el código, TAG, IMEI o número de serie.</p>
  <div class="vo-actions"><button class="vo-btn vo-btn--subtle" type="button" data-qr-scanner-open>Activar cámara</button></div>
  <video data-qr-scanner-video playsinline muted hidden></video>
  <form data-equipment-finder-form><div class="vo-field"><label for="<?= $finderId ?>-code">Código manual</label><input class="vo-input" id="<?= $finderId ?>-code" name="codigo" maxlength="255" autocomplete="off" placeholder="SC-5250" required></div><div class="ce-equipment-finder__message" data-equipment-finder-message data-qr-scanner-status role="status" aria-live="polite"></div><div class="vo-actions"><button class="vo-btn vo-btn--primary" type="submit" data-equipment-finder-submit>Buscar equipo</button><button class="vo-btn vo-btn--subtle" type="button" data-equipment-finder-close data-qr-scanner-close>Cerrar</button></div><div class="vo-actions" data-equipment-finder-result hidden><a class="vo-btn vo-btn--primary" href="#" data-equipment-finder-continue>Continuar</a></div></form>
 </dialog>
</div>
