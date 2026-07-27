<?php declare(strict_types=1); ?>
<header class="rail-section-heading">
    <h1 id="railSectionTitle">Inventario</h1>
    <p>Vista inicial para organizar el futuro inventario ferroviario.</p>
</header>
<section class="rail-empty-state" aria-labelledby="railEmptyTitle">
    <span class="rail-empty-state__icon" aria-hidden="true">▤</span>
    <h2 id="railEmptyTitle">Módulo en preparación</h2>
    <p>Subsección activa: <strong><?php echo $railEscape($railActiveSubsectionLabel); ?></strong>.</p>
    <p>Sin información operativa disponible.</p>
</section>
