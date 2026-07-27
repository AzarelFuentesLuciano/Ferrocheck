<?php declare(strict_types=1); ?>
<header class="rail-section-heading">
    <h1 id="railSectionTitle">Facturación</h1>
    <p>Espacio reservado para los procesos futuros de facturación ferroviaria.</p>
</header>
<section class="rail-empty-state" aria-labelledby="railEmptyTitle">
    <span class="rail-empty-state__icon" aria-hidden="true">$</span>
    <h2 id="railEmptyTitle">Módulo en preparación</h2>
    <p>Subsección activa: <strong><?php echo $railEscape($railActiveSubsectionLabel); ?></strong>.</p>
    <p>Esta sección queda preparada para desarrollo posterior.</p>
</section>
