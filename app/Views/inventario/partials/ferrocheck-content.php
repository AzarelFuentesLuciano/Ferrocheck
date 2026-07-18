                    <section class="vascor-module" aria-label="FerroCheck">
                        <header class="vascor-module-header">
                            <div>
                                <span class="vascor-module-header__category">Operación ferroviaria</span>
                                <h1>FerroCheck</h1>
                                <p>Consulta, validación e importación del inventario ferroviario.</p>
                            </div>
                            <span class="vascor-module-header__status"><i aria-hidden="true"></i> Operación activa</span>
                        </header>
                        <nav class="vascor-module-nav" aria-label="Secciones de FerroCheck">
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'dashboard' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=dashboard"><span aria-hidden="true">▦</span>Dashboard</a>
                            <a class="vascor-module-nav__item<?php echo ($ferroSeccion === 'consulta-vin' || $ferroSeccion === 'busqueda-multiple') ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin"><span aria-hidden="true">⌕</span>Buscar Plataformas</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'importar-excel' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel"><span aria-hidden="true">⇧</span>Importar Excel</a>
                            <a class="vascor-module-nav__item<?php echo $ferroSeccion === 'configuracion' ? ' is-active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=configuracion"><span aria-hidden="true">⚙</span>Configuración</a>
                        </nav>
                    </section>

                    <?php if ($ferroSeccion === 'dashboard'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Dashboard</span><h2>Resumen de la operación ferroviaria</h2><p>Disponibilidad de servicios e indicadores principales del inventario.</p></div>
                    </div>
                    <section id="estado" class="status-banner panel-card fade-in">
                        <div class="status-banner__head">
                            <div>
                                <p class="eyebrow">FerroCheck</p>
                                <h2>Módulo operativo integrado en VASCOR OPS</h2>
                            </div>
                            <div class="status-banner__badge">Operación activa</div>
                        </div>
                        <div class="status-list">
                            <div class="status-item">
                                <div>
                                    <strong>Importación</strong>
                                    <span>Disponible</span>
                                </div>
                                <span class="status-dot status-dot-success"></span>
                            </div>
                            <div class="status-item">
                                <div>
                                    <strong>Verificador</strong>
                                    <span>Disponible</span>
                                </div>
                                <span class="status-dot status-dot-success"></span>
                            </div>
                            <div class="status-item">
                                <div>
                                    <strong>Servidor</strong>
                                    <span>En línea</span>
                                </div>
                                <span class="status-dot status-dot-success"></span>
                            </div>
                        </div>
                        <div class="status-banner__footer">
                            <span>Última actualización</span>
                            <strong>11/07/2026 18:45</strong>
                        </div>
                    </section>

                    <section id="indicadores" class="stats-grid">
                        <article class="stat-card fade-in">
                            <div class="stat-icon">🚆</div>
                            <div>
                                <h3>Inventario Ferromex</h3>
                                <p class="counter">0</p>
                                <small>Registros</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">📍</div>
                            <div>
                                <h3>En Encantada</h3>
                                <p class="counter">0</p>
                                <small>Plataformas</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">🧭</div>
                            <div>
                                <h3>Otra ubicación</h3>
                                <p class="counter">0</p>
                                <small>Registros</small>
                            </div>
                        </article>
                        <article class="stat-card fade-in">
                            <div class="stat-icon">⚠️</div>
                            <div>
                                <h3>No encontrados</h3>
                                <p class="counter">0</p>
                                <small>Faltantes</small>
                            </div>
                        </article>
                    </section>

                    <?php elseif ($ferroSeccion === 'importar-excel'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Importar Excel</span><h2>Actualización de inventario</h2><p>Carga y valida el archivo operativo de Ferromex.</p></div>
                    </div>

                    <section id="importador" class="panel-card accordion-card fade-in">
                        <button class="accordion-toggle" type="button" aria-expanded="true">
                            <div class="accordion-title-wrap">
                                <p class="eyebrow">Importar Excel</p>
                                <h2>Importador Ferromex</h2>
                                <p>Importa el inventario de forma ordenada, segura y profesional.</p>
                            </div>
                            <span class="accordion-icon">▾</span>
                        </button>

                        <div class="accordion-content">
                            <form action="<?php echo BASE_URL; ?>/index.php" method="post" enctype="multipart/form-data" class="importador-form">
                                <label class="dropzone" for="fileInput" id="dropzone">
                                    <input type="file" id="fileInput" name="archivo" accept=".csv,.xlsx,.xls" hidden>
                                    <div class="dropzone-content">
                                        <div class="dropzone-icon">📄</div>
                                        <h2>Arrastra y suelta tu archivo aquí</h2>
                                        <p>Archivos compatibles: .xlsx y .xls</p>
                                    </div>
                                </label>

                                <div class="file-info" id="fileInfo" hidden>
                                    <div class="file-info-item">
                                        <span class="label">Archivo seleccionado:</span>
                                        <span class="value" id="fileName">-</span>
                                    </div>
                                    <div class="file-info-item">
                                        <span class="label">Tamaño:</span>
                                        <span class="value" id="fileSize">-</span>
                                    </div>
                                    <div class="file-info-item">
                                        <span class="label">Tipo:</span>
                                        <span class="value" id="fileType">-</span>
                                    </div>
                                    <div class="file-info-item">
                                        <span class="label">Registros detectados:</span>
                                        <span class="value" id="recordCount">0</span>
                                    </div>
                                    <div class="file-info-item">
                                        <span class="label">Condición del archivo:</span>
                                        <span class="value" id="fileStatus">-</span>
                                    </div>
                                </div>

                                <div class="progress-block" aria-live="polite">
                                    <div class="progress-labels">
                                        <span>Progreso</span>
                                        <span id="progressPercent">0%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" id="progressFill"></div>
                                    </div>
                                    <p class="status-message" id="statusMessage">Listo para importar</p>
                                </div>

                                <div class="actions">
                                    <label class="btn btn-secondary" for="fileInput">Seleccionar archivo</label>
                                    <button class="btn btn-primary" id="importBtn" type="button" disabled>Importar Inventario</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <?php elseif ($ferroSeccion === 'consulta-vin' || $ferroSeccion === 'busqueda-multiple'): ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Buscar Plataformas</span><h2>Búsqueda de plataformas</h2><p>Consulta uno o varios códigos de plataformas ferroviarias en una sola operación.</p></div>
                    </div>

                    <section id="verificacion" class="panel-card verifier-card fade-in">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">Buscar Plataformas</p>
                                <h2>Verificador de Plataformas</h2>
                                <p>Pegue uno o varios códigos para validar su ubicación y revisar si cuentan con evidencia.</p>
                            </div>
                        </div>
                        <textarea class="verifier-textarea" placeholder="Ejemplo: TTGX985062&#10;TTGX852741"></textarea>
                        <div class="actions actions-left">
                            <button class="btn btn-primary" type="button">Verificar</button>
                        </div>
                    </section>

                    <section id="resultados" class="result-panel fade-in">
                        <div class="result-header result-header--split">
                            <div>
                                <p class="eyebrow">Resultados</p>
                                <h3>Última verificación del operador</h3>
                            </div>
                            <button class="btn btn-secondary" type="button" id="exportExcelBtn">Exportar a Excel</button>
                        </div>
                        <div class="table-wrapper">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Transportista</th>
                                        <th>Ubicación</th>
                                        <th aria-hidden="true"></th>
                                        <th>Última actualización</th>
                                        <th>Evidencia</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="state-encantada">
                                        <td>TTGX985062</td>
                                        <td>Ferromex</td>
                                        <td>Encantada</td>
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>—</td>
                                        <td><button class="action-link" type="button">👁 Ver</button></td>
                                    </tr>
                                    <tr class="state-otra">
                                        <td>TTGX852741</td>
                                        <td>Ferromex</td>
                                        <td>Monterrey</td>
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>—</td>
                                        <td><button class="action-link" type="button">👁 Ver</button></td>
                                    </tr>
                                    <tr class="state-evidencia">
                                        <td>BNFS301330</td>
                                        <td>Kansas</td>
                                        <td>Encantada</td>
                                        <td aria-hidden="true"></td>
                                        <td>11/07/2026</td>
                                        <td>📷 Disponible</td>
                                        <td><button class="action-link" type="button">🖼 Ver foto</button></td>
                                    </tr>
                                    <tr class="state-no-encontrado">
                                        <td>TTGX741852</td>
                                        <td>Kansas</td>
                                        <td>Sin registro</td>
                                        <td aria-hidden="true"></td>
                                        <td>—</td>
                                        <td>❌</td>
                                        <td><button class="action-link" type="button">📷 Capturar</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <?php else: ?>
                    <div class="vascor-view-heading">
                        <div><span class="vascor-eyebrow">Configuración</span><h2>Preferencias de FerroCheck</h2><p>Parámetros visuales y operativos disponibles para el módulo.</p></div>
                    </div>
                    <section class="panel-card vascor-empty-state fade-in" aria-labelledby="ferroConfigTitle">
                        <div class="vascor-empty-state__icon" aria-hidden="true">⚙</div>
                        <h3 id="ferroConfigTitle">Configuración del módulo</h3>
                        <p>Esta sección conserva su estado actual y queda preparada para mostrar opciones autorizadas.</p>
                    </section>
                    <?php endif; ?>
