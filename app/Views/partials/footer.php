<?php

declare(strict_types=1);

$footerTitle = (string) ($footer['title'] ?? 'VASCOR OPS v1.0');
$footerSubtitle = (string) ($footer['subtitle'] ?? 'Plataforma Operativa');
$footerCreditLabel = (string) ($footer['creditLabel'] ?? 'Desarrollado por');
$footerDeveloper = (string) ($footer['developer'] ?? 'Ing. Azarel Fuentes Luciano');
$footerYear = (string) ($footer['year'] ?? date('Y'));
?>
<footer class="app-footer">
    <div class="app-footer__content">
        <strong class="app-footer__title"><?php echo $escape($footerTitle); ?></strong>
        <span class="app-footer__subtitle"><?php echo $escape($footerSubtitle); ?></span>
        <span class="app-footer__label"><?php echo $escape($footerCreditLabel); ?></span>
        <span class="app-footer__developer"><?php echo $escape($footerDeveloper); ?></span>
        <span class="app-footer__copyright">© <?php echo $escape($footerYear); ?> VASCOR OPS. Todos los derechos reservados.</span>
    </div>
</footer>
