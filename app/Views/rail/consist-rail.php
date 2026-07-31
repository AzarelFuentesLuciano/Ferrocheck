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
            <div class="rail-consist-message rail-consist-message--<?php echo $railEscape($message['type'] ?? 'error'); ?>"
                 role="<?php echo ($message['type'] ?? 'error') === 'error' ? 'alert' : 'status'; ?>">
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
                    $fileMessage = $consistUpload->fileMessages[$field][0] ?? null;
                    $backendError = is_array($fileMessage)
                        ? (string) ($fileMessage['message'] ?? '')
                        : '';
                    $backendState = is_array($fileMessage)
                        ? (string) ($fileMessage['type'] ?? 'error')
                        : 'pending';
                    $progressState = is_array($fileResult)
                        ? (!empty($fileResult['valid']) ? 'valid' : 'error')
                        : ($backendError !== '' ? $backendState : 'pending');
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
                            <div>
                                <dt>Identidad</dt>
                                <dd>
                                    <?php if (!empty($file['valid'])): ?>
                                        Archivo correcto · Tipo verificado:
                                        <?php echo $railEscape(
                                            $consistUpload->configuration['files'][$file['detected_file_type']]['label']
                                            ?? $file['detected_file_type']
                                            ?? '',
                                        ); ?>
                                    <?php elseif (!empty($file['identity_ambiguous'])): ?>
                                        Tipo ambiguo
                                    <?php elseif (!empty($file['identity_unknown'])): ?>
                                        Estructura desconocida
                                    <?php else: ?>
                                        Tipo no correspondiente
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Firma validada</dt>
                                <dd>
                                    <?php echo $railEscape($file['matched_identity_headers'] ?? 0); ?>
                                    de
                                    <?php echo $railEscape($file['total_identity_headers'] ?? 0); ?>
                                    columnas
                                </dd>
                            </div>
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

            <h3>Resumen ejecutivo</h3>
            <div class="rail-consist-analysis__files">
                <?php
                $executiveIndicators = [
                    'vehicle_load_report' => 'VIN en Vehicle Load',
                    'shippers' => 'VIN en Shippers',
                    'cnacs' => 'VIN en CNACS',
                    'unique' => 'VIN únicos',
                    'complete' => 'Presentes en los tres',
                    'missing_vehicle_load_report' => 'Faltantes en Vehicle Load',
                    'missing_shippers' => 'Faltantes en Shippers',
                    'missing_cnacs' => 'Faltantes en CNACS',
                    'duplicates' => 'Duplicados internos',
                    'inconsistencies' => 'Inconsistencias',
                    'candidates' => 'Candidatos al Consist',
                ];
                ?>
                <?php foreach ($executiveIndicators as $key => $label): ?>
                    <article class="rail-consist-analysis-card">
                        <h4><?php echo $railEscape($label); ?></h4>
                        <strong><?php echo $railEscape($consistUpload->analysis->summary[$key] ?? 0); ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>

            <h3>Revisión de unidades</h3>
            <form class="rail-consist-form" method="get">
                <input type="hidden" name="modulo" value="rail">
                <input type="hidden" name="seccion" value="consist-rail">
                <input type="hidden" name="subseccion" value="registrar">
                <input type="hidden" name="preview" value="<?php echo $railEscape($consistUpload->batchToken); ?>">
                <div class="rail-consist-form__files">
                    <label>Buscar VIN<input type="search" name="vin_buscar" value="<?php echo $railEscape($consistUpload->analysis->filters['vin_buscar']); ?>"></label>
                    <label>Estado<input name="estado_cruce" list="consistStatusOptions" value="<?php echo $railEscape($consistUpload->analysis->filters['estado_cruce']); ?>"></label>
                    <datalist id="consistStatusOptions"><option value="completo"><option value="faltante"><option value="duplicado"></datalist>
                    <label>Duplicados<input name="duplicados" list="consistDuplicateOptions" value="<?php echo $railEscape($consistUpload->analysis->filters['duplicados']); ?>"></label>
                    <datalist id="consistDuplicateOptions"><option value="si"><option value="no"></datalist>
                </div>
                <button class="rail-consist-submit" type="submit">Aplicar filtros</button>
            </form>
            <p>Resultados: <strong><?php echo $railEscape($consistUpload->analysis->pagination['total']); ?></strong></p>
            <?php if (empty($railCatalogPage['active'])): ?>
                <div class="rail-consist-message rail-consist-message--error" role="alert">
                    No existe un catálogo maestro de rutas activo.
                    <?php if (!empty($railCatalogPage['can_import'])): ?>
                        <a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=configuracion&amp;subseccion=catalogos">Importar catálogo maestro</a>.
                    <?php else: ?>
                        Solicite a un administrador que lo importe desde Configuración de Rail.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form method="post" class="rail-consist-form"
                  action="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=registrar">
                <input type="hidden" name="_csrf" value="<?php echo $railEscape($consistUpload->csrfToken); ?>">
                <input type="hidden" name="action" value="create_consist_draft">
                <input type="hidden" name="batch_token" value="<?php echo $railEscape($consistUpload->batchToken); ?>">
                <div class="rail-consist-form__files">
                    <label>Fecha inicial<input type="date" name="fecha_inicio" required></label>
                    <label>Fecha final<input type="date" name="fecha_fin" required></label>
                </div>
                <div class="rail-consist-table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>VIN</th><th>Vehicle Load</th><th>Shippers</th><th>CNACS</th><th>Estado</th><th>Duplicado</th><th>Observaciones</th><th>Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($consistUpload->analysis->units as $offset => $unit): ?>
                            <tr>
                                <td><?php echo $railEscape((($consistUpload->analysis->pagination['page'] - 1) * $consistUpload->analysis->pagination['per_page']) + $offset + 1); ?></td>
                                <td><?php echo $railEscape($unit['vin']); ?></td>
                                <td><?php echo !empty($unit['presence']['vehicle_load_report']) ? 'Sí' : 'No'; ?></td>
                                <td><?php echo !empty($unit['presence']['shippers']) ? 'Sí' : 'No'; ?></td>
                                <td><?php echo !empty($unit['presence']['cnacs']) ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $railEscape(ucfirst((string) $unit['status'])); ?></td>
                                <td><?php echo !empty($unit['duplicate']) ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $railEscape($unit['observations']); ?></td>
                                <td><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=registrar&amp;preview=<?php echo $railEscape($consistUpload->batchToken); ?>&amp;vin_detalle=<?php echo rawurlencode((string) $unit['vin']); ?>">Ver detalle</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="rail-consist-submit" type="submit" <?php echo empty($railCatalogPage['active']) ? 'disabled' : ''; ?>>Generar Consist</button>
            </form>

            <?php if (is_array($consistUpload->analysis->detail)): $detail = $consistUpload->analysis->detail; ?>
                <section class="rail-consist-preview-card" aria-labelledby="unitDetailTitle">
                    <h3 id="unitDetailTitle">Detalle de <?php echo $railEscape($detail['vin']); ?></h3>
                    <p>Elegibilidad: <strong><?php echo !empty($detail['eligible']) ? 'Elegible' : 'Requiere revisión'; ?></strong></p>
                    <?php foreach ($fileLabels as $sourceKey => $sourceLabel): ?>
                        <h4><?php echo $railEscape($sourceLabel); ?></h4>
                        <?php $sourceValues = $detail['source_data'][$sourceKey] ?? []; ?>
                        <?php if ($sourceValues === []): ?><p>No presente en esta fuente.</p>
                        <?php else: ?><dl class="rail-consist-meta"><?php foreach ($sourceValues as $column => $value): ?><div><dt><?php echo $railEscape($column); ?></dt><dd><?php echo $railEscape($value); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

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
            <?php $operational = $analysisResult['operational_summary'] ?? []; ?>
            <h3>Resumen operativo de plataformas</h3>
            <div class="rail-consist-analysis__files">
                <article class="rail-consist-analysis-card"><h4>Plataformas Pendientes de Confirmar</h4><strong><?php echo $railEscape($operational['pending_platforms'] ?? 0); ?></strong></article>
                <article class="rail-consist-analysis-card"><h4>Plataformas Confirmadas</h4><strong><?php echo $railEscape($operational['confirmed_platforms'] ?? 0); ?></strong></article>
                <article class="rail-consist-analysis-card"><h4>Total de Plataformas Cargadas</h4><strong><?php echo $railEscape($operational['total_loaded_platforms'] ?? 0); ?></strong></article>
            </div>
            <?php if (($operational['is_consistent'] ?? true) !== true): ?>
                <div class="rail-consist-message rail-consist-message--error" role="alert">Inconsistencia: las plataformas pendientes superan el total cargado.</div>
            <?php endif; ?>
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
<?php elseif ($railSubsection === 'consultar' && is_array($railConsistPage ?? null)): ?>
    <header class="rail-section-heading">
        <h1 id="railSectionTitle">Consist Rail</h1>
        <p>Borrador generado con trazabilidad de las columnas A:N.</p>
    </header>
    <?php if (!empty($railConsistPage['forbidden'])): ?><div class="rail-consist-message rail-consist-message--error" role="alert">No cuenta con permiso para consultar Consist Rail.</div><?php else: ?>
    <?php if (isset($railConsistPage['unit'], $railConsistPage['consist'])): $detail = $railConsistPage['unit']; $detailConsist = $railConsistPage['consist']; ?>
        <section class="rail-consist-analysis">
            <h2>Detalle de unidad <?php echo $railEscape($detail['vin']); ?></h2>
            <dl class="rail-consist-meta">
                <div><dt>Posición global</dt><dd><?php echo $railEscape($detail['global_position']); ?></dd></div>
                <div><dt>Plataforma</dt><dd><?php echo $railEscape($detail['final_data_json']['fdTransportationName1'] ?? ''); ?></dd></div>
                <div><dt>Posición en plataforma</dt><dd><?php echo $railEscape($detail['platform_position']); ?></dd></div>
                <div><dt>Route Code</dt><dd><?php echo $railEscape($detail['route_code']); ?></dd></div>
                <div><dt>Market</dt><dd><?php echo $railEscape($detail['market']); ?></dd></div>
                <div><dt>Shipping Destination</dt><dd><?php echo $railEscape($detail['shipping_destination']); ?></dd></div>
                <div><dt>Filas CNACS adicionales</dt><dd><?php echo $railEscape($detail['cnacs_additional_data_json']['count'] ?? 0); ?></dd></div>
            </dl>
            <?php foreach ([
                'final_data_json'=>'Columnas A:N',
                'vehicle_load_data_json'=>'Vehicle Load',
                'shippers_data_json'=>'Shippers',
                'cnacs_selected_data_json'=>'Primera fila CNACS seleccionada',
                'cnacs_additional_data_json'=>'Filas CNACS adicionales',
                'trace_json'=>'Trazabilidad',
                'issues_json'=>'Incidencias',
            ] as $source => $label): ?>
                <details><summary><?php echo $railEscape($label); ?></summary><dl class="rail-consist-meta"><?php foreach ($detail[$source] ?? [] as $column => $value): ?><div><dt><?php echo $railEscape($column); ?></dt><dd><?php echo $railEscape(is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)); ?></dd></div><?php endforeach; ?></dl></details>
            <?php endforeach; ?>
            <p><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=consultar&amp;id=<?php echo $railEscape($detailConsist['id']); ?>">Volver al borrador</a></p>
        </section>
    <?php else: ?>
    <section class="rail-consist-analysis">
        <?php $operational = $railConsistPage['operational_summary'] ?? []; ?>
        <div class="rail-consist-analysis__files">
            <?php foreach (['status'=>'Estado','folio'=>'Folio','fecha_inicio'=>'Fecha inicial','fecha_fin'=>'Fecha final','created_at'=>'Generado','created_by_name'=>'Usuario','total_units'=>'Unidades','total_platforms'=>'Plataformas'] as $key => $label): ?>
                <article class="rail-consist-analysis-card"><h3><?php echo $railEscape($label); ?></h3><strong><?php echo $railEscape($railConsistPage[$key] ?? ''); ?></strong></article>
            <?php endforeach; ?>
        </div>
        <div class="rail-consist-analysis__files">
            <article class="rail-consist-analysis-card"><h3>Capacidad habitual</h3><strong>Hasta 8</strong></article>
            <article class="rail-consist-analysis-card"><h3>Incidencias</h3><strong><?php echo $railEscape(count($railConsistPage['issues'] ?? [])); ?></strong></article>
            <article class="rail-consist-analysis-card"><h3>Catálogo</h3><strong><?php echo $railEscape($railConsistPage['source_filename'] ?? ''); ?></strong></article>
        </div>
        <h2>Resumen operativo de plataformas</h2>
        <div class="rail-consist-analysis__files">
            <article class="rail-consist-analysis-card"><h3>Plataformas Pendientes de Confirmar</h3><strong><?php echo $railEscape($operational['pending_platforms'] ?? 0); ?></strong></article>
            <article class="rail-consist-analysis-card"><h3>Plataformas Confirmadas</h3><strong><?php echo $railEscape($operational['confirmed_platforms'] ?? 0); ?></strong></article>
            <article class="rail-consist-analysis-card"><h3>Total de Plataformas Cargadas</h3><strong><?php echo $railEscape($operational['total_loaded_platforms'] ?? 0); ?></strong></article>
        </div>
        <p><?php echo $railEscape($operational['pending_platforms'] ?? 0); ?> pendientes + <?php echo $railEscape($operational['confirmed_platforms'] ?? 0); ?> confirmadas = <?php echo $railEscape($operational['total_loaded_platforms'] ?? 0); ?> cargadas.</p>
        <?php if (($operational['is_consistent'] ?? true) !== true): ?><div class="rail-consist-message rail-consist-message--error" role="alert">Inconsistencia: las plataformas pendientes superan el total cargado.</div><?php endif; ?>
        <form method="get" class="rail-consist-form">
            <input type="hidden" name="modulo" value="rail"><input type="hidden" name="seccion" value="consist-rail"><input type="hidden" name="subseccion" value="consultar"><input type="hidden" name="id" value="<?php echo $railEscape($railConsistPage['id']); ?>">
            <div class="rail-consist-form__files">
                <label>Buscar VIN<input type="search" name="vin_buscar" value="<?php echo $railEscape($railConsistPage['filters']['vin_buscar'] ?? ''); ?>"></label>
                <label>Plataforma<select name="plataforma"><option value="">Todas</option><?php foreach ($railConsistPage['all_platforms'] ?? [] as $platform): ?><option value="<?php echo $railEscape($platform); ?>" <?php echo ($railConsistPage['filters']['plataforma'] ?? '') === $platform ? 'selected' : ''; ?>><?php echo $railEscape($platform); ?></option><?php endforeach; ?></select></label>
                <label>Incidencia<select name="incidencia"><option value="">Todas</option><option value="si" <?php echo ($railConsistPage['filters']['incidencia'] ?? '') === 'si' ? 'selected' : ''; ?>>Con incidencia</option><option value="no" <?php echo ($railConsistPage['filters']['incidencia'] ?? '') === 'no' ? 'selected' : ''; ?>>Sin incidencia</option></select></label>
            </div><button class="rail-consist-submit" type="submit">Filtrar</button>
        </form>
        <div class="rail-consist-table-wrap">
            <table>
                <thead><tr><th>Plataforma</th><th>Posición</th><th>VIN</th><th>Track</th><th>Route Code</th><th>Shipping Destination</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($railConsistPage['units'] ?? [] as $unit): ?>
                    <tr>
                        <td><?php echo $railEscape($unit['final_data_json']['fdTransportationName1'] ?? ''); ?></td>
                        <td><?php echo $railEscape($unit['global_position']); ?></td>
                        <td><?php echo $railEscape($unit['vin']); ?></td>
                        <td><?php echo $railEscape($unit['track']); ?></td><td><?php echo $railEscape($unit['route_code']); ?></td><td><?php echo $railEscape($unit['shipping_destination']); ?></td>
                        <td><?php echo ($unit['issues_json'] ?? []) === [] ? 'Correcto' : 'Con incidencia'; ?></td>
                        <td><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=consultar&amp;id=<?php echo $railEscape($railConsistPage['id']); ?>&amp;vin_detalle=<?php echo rawurlencode((string) $unit['vin']); ?>">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p>Página <?php echo $railEscape($railConsistPage['pagination']['page'] ?? 1); ?> de <?php echo $railEscape($railConsistPage['pagination']['pages'] ?? 1); ?> · <?php echo $railEscape($railConsistPage['pagination']['total'] ?? 0); ?> unidades</p>
        <?php if (!empty($railConsistPage['can_export'])): ?><p><a class="rail-consist-submit" href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=consultar&amp;accion=exportar_consist&amp;id=<?php echo $railEscape($railConsistPage['id']); ?>">Exportar Consist Rail</a></p><?php endif; ?>
        <?php if (defined('APP_ENV') && APP_ENV !== 'production'): ?><p><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=consultar&amp;id=<?php echo $railEscape($railConsistPage['id']); ?>&amp;comparar_golden=1">Comparar con golden master</a></p><?php endif; ?>
        <?php if (isset($railConsistPage['golden_comparison'])): ?><div class="rail-consist-message <?php echo $railConsistPage['golden_comparison']['pass'] ? 'rail-consist-message--success' : 'rail-consist-message--warning'; ?>">Equivalencia: <?php echo $railEscape($railConsistPage['golden_comparison']['matches']); ?>/<?php echo $railEscape($railConsistPage['golden_comparison']['total_compared']); ?> (<?php echo $railEscape($railConsistPage['golden_comparison']['equivalence_percentage']); ?>%)</div><?php endif; ?>
        <p><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=historial">Volver al historial</a></p>
    </section>
    <?php endif; ?>
    <?php endif; ?>
<?php elseif ($railSubsection === 'historial' && is_array($railConsistPage ?? null)): ?>
    <header class="rail-section-heading"><h1 id="railSectionTitle">Historial de Consist</h1><p>Búsqueda de borradores generados.</p></header>
    <?php if (!empty($railConsistPage['forbidden'])): ?><div class="rail-consist-message rail-consist-message--error" role="alert">No cuenta con permiso para consultar el historial.</div><?php else: ?>
    <form method="get" class="rail-consist-form">
        <input type="hidden" name="modulo" value="rail"><input type="hidden" name="seccion" value="consist-rail"><input type="hidden" name="subseccion" value="historial">
        <div class="rail-consist-form__files">
            <label>Folio<input type="search" name="folio" value="<?php echo $railEscape($railConsistPage['filters']['folio'] ?? ''); ?>"></label>
            <label>VIN<input type="search" name="vin" value="<?php echo $railEscape($railConsistPage['filters']['vin'] ?? ''); ?>"></label>
            <label>Estado<input name="estado" list="consistHistoryStates" value="<?php echo $railEscape($railConsistPage['filters']['estado'] ?? ''); ?>"></label>
            <datalist id="consistHistoryStates"><option value="borrador"></datalist>
            <label>Desde<input type="date" name="desde" value="<?php echo $railEscape($railConsistPage['filters']['desde'] ?? ''); ?>"></label>
            <label>Hasta<input type="date" name="hasta" value="<?php echo $railEscape($railConsistPage['filters']['hasta'] ?? ''); ?>"></label>
            <label>Usuario<input type="search" name="usuario" value="<?php echo $railEscape($railConsistPage['filters']['usuario'] ?? ''); ?>"></label>
        </div>
        <button class="rail-consist-submit" type="submit">Buscar</button>
    </form>
    <div class="rail-consist-table-wrap"><table><thead><tr><th>Folio</th><th>Fecha</th><th>Estado</th><th>Unidades</th><th>Pendientes</th><th>Confirmadas</th><th>Total plataformas</th><th>Usuario</th><th>Catálogo</th><th>Acción</th></tr></thead><tbody>
    <?php foreach ($railConsistPage['items'] ?? [] as $item): $itemOperational = $item['operational_summary'] ?? []; ?><tr><td><?php echo $railEscape($item['folio']); ?></td><td><?php echo $railEscape($item['created_at']); ?></td><td><?php echo $railEscape($item['status']); ?></td><td><?php echo $railEscape($item['total_units']); ?></td><td><?php echo $railEscape($itemOperational['pending_platforms'] ?? 0); ?></td><td><?php echo $railEscape($itemOperational['confirmed_platforms'] ?? 0); ?></td><td><?php echo $railEscape($itemOperational['total_loaded_platforms'] ?? 0); ?></td><td><?php echo $railEscape($item['created_by_name']); ?></td><td><?php echo $railEscape($item['source_filename']); ?></td><td><a href="<?php echo $railEscape($railBaseUrl); ?>/index.php?modulo=rail&amp;seccion=consist-rail&amp;subseccion=consultar&amp;id=<?php echo $railEscape($item['id']); ?>">Ver</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <p>Página <?php echo $railEscape($railConsistPage['page']); ?> de <?php echo $railEscape($railConsistPage['pages']); ?> · <?php echo $railEscape($railConsistPage['total']); ?> resultados</p>
    <?php endif; ?>
<?php elseif ($railSubsection === 'dashboard'): ?>
    <section class="rail-consist-analysis" aria-labelledby="summaryPendingTitle">
        <h2 id="summaryPendingTitle">Summary pendiente de validación operativa</h2>
        <div class="rail-consist-analysis__files">
            <article class="rail-consist-analysis-card"><h3>Unidades</h3><strong>Dinámicas por lote</strong></article>
            <article class="rail-consist-analysis-card"><h3>Plataformas</h3><strong>Dinámicas por lote</strong></article>
            <article class="rail-consist-analysis-card"><h3>Summary</h3><strong>Regla reconciliada</strong></article>
            <article class="rail-consist-analysis-card"><h3>Estado</h3><strong>No aprobado para producción</strong></article>
        </div>
        <p>Esta limitación no bloquea la generación del Consist.</p>
    </section>
<?php else: ?>
    <section class="rail-empty-state" aria-labelledby="railEmptyTitle">
        <span class="rail-empty-state__icon" aria-hidden="true">≋</span>
        <h2 id="railEmptyTitle">Módulo en preparación</h2>
        <p>Subsección activa: <strong><?php echo $railEscape($railActiveSubsectionLabel); ?></strong>.</p>
        <p>Sin información operativa disponible.</p>
    </section>
<?php endif; ?>
