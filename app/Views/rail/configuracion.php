<?php declare(strict_types=1); ?>
<header class="rail-section-heading">
    <h1 id="railSectionTitle">Configuración</h1>
    <p>Vista inicial para parámetros y catálogos internos exclusivos de Rail.</p>
</header>
<?php if ($railSubsection === 'catalogos'): ?>
<section class="rail-consist-upload" aria-labelledby="routeCatalogTitle">
    <div class="rail-consist-upload__heading">
        <h2 id="routeCatalogTitle">Catálogo maestro de rutas</h2>
        <p>Importa una versión validada de <code>vascor_sm_db.xlsx</code>. La hoja obligatoria es <code>route_codes</code>.</p>
    </div>
    <?php foreach ($railMessages as $message): ?>
        <div class="rail-consist-message rail-consist-message--<?php echo $railEscape($message['type'] ?? 'error'); ?>"
             role="<?php echo ($message['type'] ?? 'error') === 'error' ? 'alert' : 'status'; ?>">
            <?php echo $railEscape($message['message'] ?? ''); ?>
        </div>
    <?php endforeach; ?>
    <?php if (!empty($railCatalogPage['active'])): $version = (array) $railCatalogPage['version']; ?>
        <dl class="rail-consist-meta">
            <div><dt>Estado</dt><dd>Activo</dd></div>
            <div><dt>Archivo</dt><dd><?php echo $railEscape($version['source_filename'] ?? ''); ?></dd></div>
            <div><dt>SHA-256</dt><dd><code><?php echo $railEscape($version['source_sha256'] ?? ''); ?></code></dd></div>
            <div><dt>Rutas</dt><dd><?php echo $railEscape($version['record_count'] ?? 0); ?></dd></div>
            <div><dt>Importado</dt><dd><?php echo $railEscape($version['imported_at'] ?? ''); ?></dd></div>
        </dl>
    <?php else: ?>
        <div class="rail-consist-message rail-consist-message--error" role="alert">
            No existe un catálogo maestro activo. Generar Consist permanecerá bloqueado hasta importar uno válido.
        </div>
    <?php endif; ?>
    <?php if (!empty($railCatalogPage['can_import'])): ?>
        <form class="rail-consist-form" method="post" enctype="multipart/form-data"
              action="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=configuracion&amp;subseccion=catalogos">
            <input type="hidden" name="_csrf" value="<?php echo $railEscape($railCsrfToken); ?>">
            <input type="hidden" name="action" value="import_route_catalog">
            <label>Archivo XLSX
                <input type="file" name="route_catalog" accept=".xlsx" required>
            </label>
            <button class="rail-consist-submit" type="submit">Importar y activar catálogo</button>
        </form>
    <?php else: ?>
        <p>No cuenta con permiso para importar catálogos.</p>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="rail-empty-state" aria-labelledby="railEmptyTitle">
    <span class="rail-empty-state__icon" aria-hidden="true">⚙</span>
    <h2 id="railEmptyTitle">Módulo en preparación</h2>
    <p>Subsección activa: <strong><?php echo $railEscape($railActiveSubsectionLabel); ?></strong>.</p>
    <p>Sin información operativa disponible.</p>
</section>
<?php endif; ?>
