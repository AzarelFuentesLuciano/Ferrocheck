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
              data-consist-upload-form
              action="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=registrar">
            <input type="hidden" name="_csrf" value="<?php echo $railEscape($consistUpload->csrfToken); ?>">
            <div class="rail-consist-form__files">
                <?php foreach ($consistUpload->configuration['files'] ?? [] as $field => $definition): ?>
                    <?php
                    $fileResult = $consistUpload->preview['files'][$field] ?? null;
                    $backendError = '';
                    foreach ($consistUpload->messages as $message) {
                        if (($message['type'] ?? '') === 'error') {
                            $backendError = (string) ($message['message'] ?? '');
                            break;
                        }
                    }
                    $progressState = is_array($fileResult)
                        ? (!empty($fileResult['valid']) ? 'valid' : 'error')
                        : ($backendError !== '' ? 'error' : 'pending');
                    $progressValue = $progressState === 'valid' ? 100 : 0;
                    $fileError = is_array($fileResult) && isset($fileResult['errors'][0])
                        ? (string) $fileResult['errors'][0]
                        : '';
                    $progressMessage = $progressState === 'valid'
                        ? 'Archivo validado correctamente.'
                        : ($progressState === 'error' ? ($fileError !== '' ? $fileError : $backendError) : 'Pendiente de validación.');
                    ?>
                    <label class="rail-consist-file" data-consist-file data-state="<?php echo $railEscape($progressState); ?>">
                        <span><?php echo $railEscape($definition['label'] ?? $field); ?></span>
                        <input type="file" name="<?php echo $railEscape($field); ?>" accept=".xlsx,.xls,.csv" required>
                        <div class="progress-block" aria-live="polite">
                            <div class="progress-labels">
                                <span data-progress-state><?php echo $railEscape(ucfirst($progressState === 'valid' ? 'validado' : $progressState)); ?></span>
                                <span data-progress-percent><?php echo $railEscape($progressValue); ?> %</span>
                            </div>
                            <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                 aria-valuenow="<?php echo $railEscape($progressValue); ?>">
                                <div class="progress-bar-fill" data-progress-fill style="width: <?php echo $railEscape($progressValue); ?>%"></div>
                            </div>
                            <p class="status-message" data-progress-message><?php echo $railEscape($progressMessage); ?></p>
                        </div>
                        <small>XLSX · XLS · CSV<br>Máximo 10 MB</small>
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
            <?php if ($consistUpload->canAnalyze): ?>
                <form class="rail-consist-analysis-action" method="post" data-consist-analysis-form
                      action="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=registrar">
                    <input type="hidden" name="_csrf" value="<?php echo $railEscape($consistUpload->csrfToken); ?>">
                    <input type="hidden" name="action" value="analyze_vin_cross">
                    <input type="hidden" name="batch_token" value="<?php echo $railEscape($consistUpload->batchToken); ?>">
                    <button class="rail-consist-submit" type="submit" data-analysis-submit>
                        <?php echo $consistUpload->analysis === null ? 'Analizar cruce de VIN' : 'Analizar nuevamente'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($consistUpload->analysis instanceof \App\ViewModels\Rail\ConsistAnalysisViewModel): ?>
        <?php
        $analysisResult = $consistUpload->analysis->result;
        $fileLabels = [
            'vehicle_load_report' => 'Vehicle Load Report',
            'shippers' => 'Shippers',
            'cnacs' => 'CNACS',
        ];
        $categoryLabels = [
            'present_in_all' => ['Presentes en los tres', 'all'],
            'vehicle_load_report_and_shippers' => ['Vehicle Load Report + Shippers', 'two'],
            'vehicle_load_report_and_cnacs' => ['Vehicle Load Report + CNACS', 'two'],
            'shippers_and_cnacs' => ['Shippers + CNACS', 'two'],
            'only_vehicle_load_report' => ['Solo Vehicle Load Report', 'one'],
            'only_shippers' => ['Solo Shippers', 'one'],
            'only_cnacs' => ['Solo CNACS', 'one'],
        ];
        ?>
        <section class="rail-consist-analysis" aria-labelledby="consistAnalysisTitle">
            <div class="rail-consist-upload__heading">
                <h2 id="consistAnalysisTitle">Cruce completado</h2>
                <p>Resumen temporal del lote basado exclusivamente en coincidencias de VIN.</p>
            </div>

            <h3>Resumen del lote</h3>
            <div class="rail-consist-analysis__files">
                <?php foreach ($fileLabels as $key => $label): $summary = $analysisResult['files'][$key] ?? []; ?>
                    <article class="rail-consist-analysis-card">
                        <h4><?php echo $railEscape($label); ?></h4>
                        <dl>
                            <div><dt>Registros con VIN</dt><dd><?php echo $railEscape($summary['valid_records'] ?? 0); ?></dd></div>
                            <div><dt>VIN únicos</dt><dd><?php echo $railEscape($summary['unique_vins'] ?? 0); ?></dd></div>
                            <div><dt>Duplicados</dt><dd><?php echo $railEscape($summary['duplicates'] ?? 0); ?></dd></div>
                            <div><dt>VIN vacíos</dt><dd><?php echo $railEscape($summary['empty_vin'] ?? 0); ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>

            <h3>Cruce de VIN</h3>
            <div class="rail-consist-analysis__categories">
                <?php foreach ($categoryLabels as $key => [$label, $tone]): $category = $analysisResult['categories'][$key] ?? []; ?>
                    <details class="rail-consist-cross-card rail-consist-cross-card--<?php echo $railEscape($tone); ?>">
                        <summary>
                            <span><?php echo $railEscape($label); ?></span>
                            <strong><?php echo $railEscape($category['count'] ?? 0); ?></strong>
                        </summary>
                        <?php if (($category['sample'] ?? []) !== []): ?>
                            <ul><?php foreach ($category['sample'] as $vin): ?><li><?php echo $railEscape($vin); ?></li><?php endforeach; ?></ul>
                            <?php if (($category['remaining'] ?? 0) > 0): ?>
                                <p>Y <?php echo $railEscape($category['remaining']); ?> registros adicionales.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Sin VIN en esta categoría.</p>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>

            <h3>Duplicados internos</h3>
            <div class="rail-consist-analysis__categories">
                <?php foreach ($fileLabels as $key => $label): $category = $analysisResult['categories']['duplicates_' . $key] ?? []; ?>
                    <details class="rail-consist-cross-card">
                        <summary>
                            <span><?php echo $railEscape($label); ?></span>
                            <strong><?php echo $railEscape($category['count'] ?? 0); ?></strong>
                        </summary>
                        <?php if (($category['sample'] ?? []) !== []): ?>
                            <ul><?php foreach ($category['sample'] as $vin): ?><li><?php echo $railEscape($vin); ?></li><?php endforeach; ?></ul>
                            <?php if (($category['remaining'] ?? 0) > 0): ?>
                                <p>Y <?php echo $railEscape($category['remaining']); ?> registros adicionales.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Sin VIN duplicados.</p>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>

            <?php $consistency = $analysisResult['consistency'] ?? []; ?>
            <h3>Consistencia</h3>
            <dl class="rail-consist-consistency">
                <div><dt>Total único combinado</dt><dd><?php echo $railEscape($consistency['total_unique_combined'] ?? 0); ?></dd></div>
                <div><dt>Encontrados en los tres</dt><dd><?php echo $railEscape($consistency['total_present_in_all'] ?? 0); ?></dd></div>
                <div><dt>Con alguna ausencia</dt><dd><?php echo $railEscape($consistency['total_with_any_absence'] ?? 0); ?></dd></div>
                <div><dt>Duplicados internos</dt><dd><?php echo $railEscape($consistency['total_internal_duplicates'] ?? 0); ?></dd></div>
                <div><dt>VIN vacíos</dt><dd><?php echo $railEscape($consistency['total_empty_vins'] ?? 0); ?></dd></div>
            </dl>
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
