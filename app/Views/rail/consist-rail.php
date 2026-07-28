<?php declare(strict_types=1); ?>
<header class="rail-section-heading">
    <h1 id="railSectionTitle">Consist Rail</h1>
    <p>Carga y validación inicial de archivos para composiciones ferroviarias.</p>
</header>
<?php if ($railSubsection === 'registrar' && $consistUpload instanceof \App\ViewModels\Rail\ConsistUploadViewModel): ?>
    <section class="rail-consist-upload" aria-labelledby="consistUploadTitle">
        <div class="rail-consist-upload__heading">
            <h2 id="consistUploadTitle">Nuevo Consist</h2>
            <p>Carga los tres archivos del mismo lote. En esta fase solo se validarán y resguardarán temporalmente.</p>
        </div>
        <?php foreach ($consistUpload->messages as $message): ?>
            <div class="rail-consist-message rail-consist-message--<?php echo $railEscape($message['type'] ?? 'error'); ?>" role="status">
                <?php echo $railEscape($message['message'] ?? ''); ?>
            </div>
        <?php endforeach; ?>
        <form class="rail-consist-form" method="post" enctype="multipart/form-data"
              action="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=registrar">
            <input type="hidden" name="_csrf" value="<?php echo $railEscape($consistUpload->csrfToken); ?>">
            <div class="rail-consist-form__files">
                <?php foreach ($consistUpload->configuration['files'] ?? [] as $field => $definition): ?>
                    <label class="rail-consist-file">
                        <span><?php echo $railEscape($definition['label'] ?? $field); ?></span>
                        <input type="file" name="<?php echo $railEscape($field); ?>" accept=".xlsx,.xls,.csv" required>
                        <small>XLSX, XLS o CSV · máximo 10 MB</small>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="rail-consist-submit" type="submit">Cargar y validar</button>
        </form>
    </section>
    <?php if (is_array($consistUpload->preview)): ?>
        <section class="rail-consist-preview" aria-labelledby="consistPreviewTitle">
            <div class="rail-consist-upload__heading">
                <h2 id="consistPreviewTitle">Vista previa del lote</h2>
                <p><?php echo $consistUpload->preview['valid']
                    ? 'Los tres archivos cumplen las validaciones de esta fase.'
                    : 'El lote no puede continuar mientras existan errores.'; ?></p>
            </div>
            <div class="rail-consist-preview__grid">
                <?php foreach ($consistUpload->preview['files'] ?? [] as $file): ?>
                    <article class="rail-consist-preview-card">
                        <header>
                            <h3><?php echo $railEscape($file['label'] ?? 'Archivo'); ?></h3>
                            <span class="rail-consist-status"><?php echo !empty($file['valid']) ? 'Válido' : 'Revisar'; ?></span>
                        </header>
                        <dl class="rail-consist-meta">
                            <div><dt>Archivo</dt><dd><?php echo $railEscape($file['original_name'] ?? ''); ?></dd></div>
                            <div><dt>Tipo</dt><dd><?php echo $railEscape($file['detected_type'] ?? ''); ?></dd></div>
                            <div><dt>Tamaño</dt><dd><?php echo $railEscape(number_format(((int) ($file['size'] ?? 0)) / 1024, 1)); ?> KB</dd></div>
                            <div><dt>Hoja</dt><dd><?php echo $railEscape($file['sheet'] ?? 'No detectada'); ?></dd></div>
                            <div><dt>Fila de encabezado</dt><dd><?php echo $railEscape($file['header_row'] ?? 'No detectada'); ?></dd></div>
                            <div><dt>Columnas encontradas</dt><dd><?php echo $railEscape(implode(', ', $file['found_columns'] ?? [])); ?></dd></div>
                            <div><dt>Columnas faltantes</dt><dd><?php echo $railEscape(implode(', ', $file['missing_required'] ?? []) ?: 'Ninguna'); ?></dd></div>
                            <div><dt>Registros válidos</dt><dd><?php echo $railEscape($file['valid_records'] ?? 0); ?></dd></div>
                            <div><dt>Filas vacías</dt><dd><?php echo $railEscape($file['empty_rows'] ?? 0); ?></dd></div>
                            <div><dt>VIN vacíos</dt><dd><?php echo $railEscape($file['empty_vin'] ?? 0); ?></dd></div>
                            <div><dt>VIN duplicados</dt><dd><?php echo $railEscape($file['duplicate_vin'] ?? 0); ?></dd></div>
                        </dl>
                        <?php if (($file['missing_required'] ?? []) !== []): ?>
                            <p class="rail-consist-error">Columnas obligatorias ausentes: <?php echo $railEscape(implode(', ', $file['missing_required'])); ?></p>
                        <?php endif; ?>
                        <?php if (($file['errors'] ?? []) !== []): ?>
                            <ul class="rail-consist-errors">
                                <?php foreach ($file['errors'] as $error): ?><li><?php echo $railEscape($error); ?></li><?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (($file['sample'] ?? []) !== []): ?>
                            <div class="rail-consist-table-wrap">
                                <table>
                                    <thead><tr><th>Fila</th><th>VIN normalizado</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($file['sample'] as $row): ?>
                                        <tr><td><?php echo $railEscape($row['row']); ?></td><td><?php echo $railEscape($row['vin']); ?></td></tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section class="rail-empty-state" aria-labelledby="railEmptyTitle">
        <span class="rail-empty-state__icon" aria-hidden="true">≋</span>
        <h2 id="railEmptyTitle">Módulo en preparación</h2>
        <p>Subsección activa: <strong><?php echo $railEscape($railActiveSubsectionLabel); ?></strong>.</p>
        <p>Sin información operativa disponible.</p>
    </section>
<?php endif; ?>
