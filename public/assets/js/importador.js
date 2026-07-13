document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('fileInput');
    const dropzone = document.getElementById('dropzone');
    const fileInfo = document.getElementById('fileInfo');
    const accordionToggle = document.querySelector('.accordion-toggle');
    const accordionContent = document.querySelector('.accordion-content');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    const recordCount = document.getElementById('recordCount');
    const fileStatus = document.getElementById('fileStatus');
    const importBtn = document.getElementById('importBtn');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const statusMessage = document.getElementById('statusMessage');
    const verifierTextarea = document.querySelector('.verifier-textarea');
    const verifyBtn = document.querySelector('#verificacion .actions .btn.btn-primary');
    const resultsTableBody = document.querySelector('.results-table tbody');
    const currentDate = document.getElementById('currentDate');
    const currentTime = document.getElementById('currentTime');
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    const tickerDate = document.querySelector('.ticker-date');
    const tickerTime = document.querySelector('.ticker-time');
    const tickerLastUpdate = document.querySelector('.ticker-last-update');
    const ultimaImportacionLabel = document.querySelector('.status-banner__footer span');
    const ultimaImportacionValor = document.querySelector('.status-banner__footer strong');
    const infoItems = Array.from(document.querySelectorAll('.info-panel__item'));
    let lastTimeText = '';
    let currentInfoIndex = 0;
    let panelTimer = null;

    const statCardsByTitle = {};
    document.querySelectorAll('.stat-card').forEach((card) => {
        const title = card.querySelector('h3')?.textContent?.trim();
        const counter = card.querySelector('.counter');
        if (title && counter) {
            statCardsByTitle[title] = counter;
        }
    });

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const estadoTexto = (estado) => {
        if (estado === 'EN_ENCANTADA') return 'EN ENCANTADA';
        if (estado === 'OTRA_UBICACION') return 'OTRA UBICACION';
        return 'NO ENCONTRADO';
    };

    const estadoClass = (estado) => {
        if (estado === 'EN_ENCANTADA') return 'status-encantada';
        if (estado === 'OTRA_UBICACION') return 'status-otra';
        return 'status-no-encontrado';
    };

    const filaClass = (estado) => {
        if (estado === 'EN_ENCANTADA') return 'state-encantada';
        if (estado === 'OTRA_UBICACION') return 'state-otra';
        return 'state-no-encontrado';
    };

    const ubicacionClass = (ubicacion) => {
        const texto = String(ubicacion ?? '').trim().toUpperCase();

        if (texto === '' || texto.includes('SIN REGISTRO') || texto.includes('NO ENCONTRADO')) {
            return 'ubicacion-no-encontrado';
        }

        if (texto.includes('ENCANTA')) {
            return 'ubicacion-encantada';
        }

        if (texto.includes('PATIO')) {
            return 'ubicacion-patio';
        }

        if (texto.includes('VÍA') || texto.includes('VIA')) {
            return 'ubicacion-via';
        }

        if (texto.includes('TALLER')) {
            return 'ubicacion-taller';
        }

        return 'ubicacion-default';
    };

    const etiquetaFecha = (valor) => {
        if (!valor) {
            return '—';
        }

        const fecha = new Date(valor);
        if (Number.isNaN(fecha.getTime())) {
            return String(valor);
        }

        return fecha.toLocaleString('es-MX');
    };

    const obtenerValor = (detalle, campo) => {
        const valor = detalle?.[campo];
        if (valor === null || valor === undefined || String(valor).trim() === '') {
            return '—';
        }
        return String(valor);
    };

    const seccionesDetalle = [
        {
            titulo: 'Informacion General',
            campos: [
                { label: 'Equipo', key: 'equipo' },
                { label: 'Producto', key: 'producto' },
                { label: 'Tipo de embarque', key: 'tipo_de_embarque' },
                { label: 'Tipo de equipo', key: 'tipo_especifico_de_equipo' },
                { label: 'Tipo generico', key: 'tipo_generico_de_equipo' }
            ]
        },
        {
            titulo: 'Ubicacion',
            campos: [
                { label: 'Estacion', key: 'estacion' },
                { label: 'Estacion origen', key: 'estacion_de_origen' },
                { label: 'Estacion destino', key: 'estacion_de_destino' },
                { label: 'Estacion ultimo movimiento', key: 'estacion_de_ultimo_movimiento' },
                { label: 'Ferrocarril', key: 'ferrocarril' },
                { label: 'Ferrocarril actual', key: 'ferrocarril_actual' },
                { label: 'Ruta', key: 'ruta' }
            ]
        },
        {
            titulo: 'Movimiento',
            campos: [
                { label: 'Ultimo movimiento', key: 'ultimo_movimiento' },
                { label: 'Fecha ultimo movimiento', key: 'fecha_de_ultimo_movimiento' },
                { label: 'ETA', key: 'eta' },
                { label: 'ETI', key: 'eti' },
                { label: 'Disponible', key: 'disponible' },
                { label: 'Estatus del viaje', key: 'estatus_del_viaje' },
                { label: 'Estatus de situado', key: 'estatus_de_situado' }
            ]
        },
        {
            titulo: 'Carga',
            campos: [
                { label: 'Remitente', key: 'remitente' },
                { label: 'Consignatario', key: 'consignatario' },
                { label: 'Peso', key: 'peso_total_del_carro' },
                { label: 'Toneladas', key: 'ton_neta' },
                { label: 'Longitud', key: 'longitud_de_carro' },
                { label: 'Limite de carga', key: 'limite_de_carga' }
            ]
        },
        {
            titulo: 'Operacion',
            campos: [
                { label: 'Numero de guia', key: 'numero_de_guia' },
                { label: 'Numero BOL', key: 'numero_de_bol' },
                { label: 'ID Tren', key: 'id_de_tren' },
                { label: 'Indicador de actividad', key: 'indicador_de_actividad' },
                { label: 'Dia remitido', key: 'dia_remitido' }
            ]
        }
    ];

    let detalleModal = null;

    const crearDetalleModal = () => {
        if (detalleModal) {
            return detalleModal;
        }

        const modal = document.createElement('div');
        modal.id = 'detallePlataformaModal';
        modal.style.position = 'fixed';
        modal.style.inset = '0';
        modal.style.background = 'rgba(10, 16, 32, 0.62)';
        modal.style.display = 'none';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.padding = '20px';
        modal.style.zIndex = '9999';

        modal.innerHTML = `
            <div style="width:min(1080px,96vw);max-height:90vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 22px 60px rgba(0,0,0,.28);padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;">
                    <div>
                        <p style="margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Detalle de Plataforma</p>
                        <h3 id="detalleModalTitulo" style="margin:4px 0 0;font-size:22px;color:#0f172a;">Equipo</h3>
                    </div>
                    <button type="button" id="detalleModalCerrar" style="border:none;background:#e2e8f0;color:#0f172a;border-radius:10px;padding:8px 12px;cursor:pointer;">Cerrar</button>
                </div>
                <p id="detalleModalMeta" style="margin:0 0 14px;color:#64748b;font-size:13px;"></p>
                <div id="detalleModalContenido"></div>
                <div style="margin-top:22px;padding:16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                    <h4 style="margin:0 0 8px;color:#0f172a;">Evidencias</h4>
                    <p style="margin:0 0 10px;color:#334155;">No existen evidencias registradas</p>
                    <button type="button" disabled style="border:none;background:#cbd5e1;color:#334155;border-radius:10px;padding:10px 14px;cursor:not-allowed;">Proximamente</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        modal.querySelector('#detalleModalCerrar')?.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        detalleModal = modal;
        return modal;
    };

    const renderDetalleSecciones = (detalle) => {
        const bloques = seccionesDetalle.map((seccion) => {
            const filas = seccion.campos.map((campo) => {
                return `
                    <div style="display:grid;grid-template-columns:220px 1fr;gap:10px;padding:6px 0;border-bottom:1px dashed #e2e8f0;">
                        <strong style="color:#334155;">${escapeHtml(campo.label)}</strong>
                        <span style="color:#0f172a;">${escapeHtml(obtenerValor(detalle, campo.key))}</span>
                    </div>
                `;
            }).join('');

            return `
                <section style="padding:14px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <h4 style="margin:0 0 10px;color:#0f172a;">${escapeHtml(seccion.titulo)}</h4>
                    ${filas}
                </section>
            `;
        }).join('');

        return `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;">${bloques}</div>`;
    };

    const abrirDetalleModal = (payload) => {
        const modal = crearDetalleModal();
        const titulo = modal.querySelector('#detalleModalTitulo');
        const meta = modal.querySelector('#detalleModalMeta');
        const contenido = modal.querySelector('#detalleModalContenido');

        if (!titulo || !meta || !contenido) {
            return;
        }

        if (!payload?.success || !payload.data) {
            titulo.textContent = 'Detalle no disponible';
            meta.textContent = '';
            contenido.innerHTML = `<p style="margin:0;padding:14px;border-radius:10px;background:#fff1f2;color:#be123c;">${escapeHtml(payload?.message || 'El equipo solicitado ya no existe en el inventario.')}</p>`;
            modal.style.display = 'flex';
            return;
        }

        const detalle = payload.data;
        titulo.textContent = `Equipo ${obtenerValor(detalle, 'equipo')}`;
        meta.textContent = `Ultima actualizacion: ${etiquetaFecha(detalle.fecha_importacion || detalle.fecha_actualizacion || '')}`;
        contenido.innerHTML = renderDetalleSecciones(detalle);
        modal.style.display = 'flex';
    };

    const solicitarDetalleEquipo = async (codigoEquipo) => {
        const body = new URLSearchParams();
        body.set('codigo_equipo', codigoEquipo);

        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();
        if (!response.ok) {
            return {
                success: false,
                message: payload?.message || 'No se pudo obtener el detalle del equipo.'
            };
        }

        return payload;
    };

    const actualizarTarjetasVerificador = (respuesta) => {
        const data = respuesta?.data ?? respuesta ?? {};

        if (statCardsByTitle['Inventario Ferromex']) {
            statCardsByTitle['Inventario Ferromex'].textContent = String(data.inventario_ferromex ?? 0);
        }
        if (statCardsByTitle['En Encantada']) {
            statCardsByTitle['En Encantada'].textContent = String(data.en_encantada ?? 0);
        }
        if (statCardsByTitle['Otra ubicación']) {
            statCardsByTitle['Otra ubicación'].textContent = String(data.otra_ubicacion ?? 0);
        }
        if (statCardsByTitle['No encontrados']) {
            statCardsByTitle['No encontrados'].textContent = String(data.no_encontrado ?? 0);
        }

        if ('ultima_actualizacion' in data && ultimaImportacionLabel && ultimaImportacionValor) {
            ultimaImportacionLabel.textContent = '📅 Última importación';
            ultimaImportacionValor.textContent = formatearUltimaImportacion(data.ultima_actualizacion);
        }
    };

    const formatearUltimaImportacion = (fechaMysql) => {
        if (!fechaMysql || String(fechaMysql).trim() === '') {
            return 'Sin registros';
        }

        const valor = String(fechaMysql).trim();
        const fecha = new Date(valor.replace(' ', 'T'));

        if (Number.isNaN(fecha.getTime())) {
            return 'Sin registros';
        }

        const formatter = new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });

        return formatter.format(fecha).replace(',', '');
    };

    const cargarResumenDashboard = async () => {
        try {
            const body = new URLSearchParams();
            body.set('dashboard_stats', '1');

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString()
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                return;
            }

            actualizarTarjetasVerificador(result.data || {});
        } catch (_error) {
            // Keep UI stable if dashboard refresh fails.
        }
    };

    const renderResultadosVerificador = (resultados) => {
        if (!resultsTableBody) {
            return;
        }

        if (!Array.isArray(resultados) || resultados.length === 0) {
            resultsTableBody.innerHTML = '<tr><td colspan="7">No hay resultados para mostrar.</td></tr>';
            return;
        }

        const rows = resultados.map((item) => {
            return `
                <tr class="${filaClass(item.estado)}">
                    <td>${escapeHtml(item.codigo)}</td>
                    <td>${escapeHtml(item.transportista || '—')}</td>
                    <td><span class="ubicacion-pill ${ubicacionClass(item.ubicacion)}">${escapeHtml(item.ubicacion || 'Sin registro')}</span></td>
                    <td><span class="status-pill ${estadoClass(item.estado)}">${escapeHtml(estadoTexto(item.estado))}</span></td>
                    <td>${escapeHtml(item.ultima_actualizacion || '—')}</td>
                    <td>${escapeHtml(item.evidencia || '—')}</td>
                    <td><button class="action-link" type="button">${escapeHtml(item.accion || 'Ver')}</button></td>
                </tr>
            `;
        }).join('');

        resultsTableBody.innerHTML = rows;
    };

    const renderInfoPanel = () => {
        infoItems.forEach((item, index) => {
            const isActive = index === currentInfoIndex;
            item.classList.toggle('info-panel__item--active', isActive);
            item.classList.toggle('is-active', isActive);
            item.classList.remove('is-exiting');
        });
    };

    const cycleInfoPanel = () => {
        const currentItem = infoItems[currentInfoIndex];
        if (!currentItem) {
            return;
        }

        currentItem.classList.add('is-exiting');
        setTimeout(() => {
            currentItem.classList.remove('is-exiting');
            currentInfoIndex = (currentInfoIndex + 1) % infoItems.length;
            renderInfoPanel();
        }, 320);
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const updateClock = () => {
        const now = new Date();
        const formattedDate = now.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
        const formattedTime = now.toLocaleTimeString('es-MX');

        currentDate.textContent = formattedDate;
        currentTime.textContent = formattedTime;

        if (tickerDate) {
            tickerDate.textContent = `Fecha completa: ${new Date().toLocaleDateString('es-MX', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            })}`;
        }

        if (tickerTime) {
            tickerTime.textContent = `Hora completa: ${new Date().toLocaleTimeString('es-MX')}`;
        }

        if (tickerLastUpdate) {
            tickerLastUpdate.textContent = `Última actualización: ${new Date().toLocaleString('es-MX', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            })}`;
        }

        if (currentTime.textContent !== lastTimeText) {
            currentTime.animate([
                { opacity: 0.7, transform: 'translateY(1px)' },
                { opacity: 1, transform: 'translateY(0)' }
            ], {
                duration: 280,
                easing: 'ease-out'
            });
            lastTimeText = currentTime.textContent;
        }

        renderInfoPanel();
    };

    const resetProgress = () => {
        progressFill.style.width = '0%';
        progressPercent.textContent = '0%';
        statusMessage.textContent = 'Listo para importar';
        statusMessage.style.color = 'var(--success)';
    };

    const updateFileDetails = (file) => {
        if (!file) {
            fileInfo.hidden = true;
            importBtn.disabled = true;
            recordCount.textContent = '0';
            fileStatus.textContent = '-';
            resetProgress();
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileType.textContent = file.type || 'Tipo no disponible';
        fileInfo.hidden = false;
        importBtn.disabled = true;
        recordCount.textContent = '0';
        fileStatus.textContent = 'Analizando...';
        resetProgress();

        const formData = new FormData();
        formData.append('archivo', file);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then((response) => response.json())
            .then((result) => {
                recordCount.textContent = result.registros ?? 0;
                fileStatus.textContent = result.estado ?? 'No válido';
                importBtn.disabled = !result.valido;
                statusMessage.textContent = result.valido
                    ? 'Análisis previo completado. La importación se realizará en una siguiente fase.'
                    : 'Archivo no válido. Revise el contenido del Excel.';
                statusMessage.style.color = result.valido ? 'var(--success)' : 'var(--danger)';
                progressFill.style.width = '100%';
                progressPercent.textContent = '100%';
            })
            .catch(() => {
                recordCount.textContent = '0';
                fileStatus.textContent = 'No válido';
                importBtn.disabled = true;
                statusMessage.textContent = 'No se pudo analizar el archivo';
                statusMessage.style.color = 'var(--danger)';
                progressFill.style.width = '0%';
                progressPercent.textContent = '0%';
            });
    };

    let desktopCollapsed = false;
    let mobileSidebarOpen = false;

    const updateSidebarState = () => {
        if (!sidebar || !menuToggle) {
            return;
        }

        const isMobile = window.matchMedia('(max-width: 920px)').matches;

        if (isMobile) {
            sidebar.classList.remove('is-collapsed');
            sidebar.classList.toggle('is-open', mobileSidebarOpen);
            sidebarBackdrop?.classList.toggle('is-visible', mobileSidebarOpen);
            document.body.classList.toggle('is-sidebar-open', mobileSidebarOpen);
            menuToggle.setAttribute('aria-expanded', String(mobileSidebarOpen));
            menuToggle.querySelector('.menu-toggle__icon').textContent = mobileSidebarOpen ? '✕' : '☰';
            return;
        }

        sidebar.classList.toggle('is-collapsed', desktopCollapsed);
        sidebar.classList.remove('is-open');
        sidebarBackdrop?.classList.remove('is-visible');
        document.body.classList.remove('is-sidebar-open');
        menuToggle.setAttribute('aria-expanded', String(!desktopCollapsed));
        menuToggle.querySelector('.menu-toggle__icon').textContent = '☰';
    };

    menuToggle?.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 920px)').matches) {
            mobileSidebarOpen = !mobileSidebarOpen;
        } else {
            desktopCollapsed = !desktopCollapsed;
        }
        updateSidebarState();
    });

    sidebarBackdrop?.addEventListener('click', () => {
        mobileSidebarOpen = false;
        updateSidebarState();
    });

    document.querySelectorAll('.sidebar__item').forEach((item) => {
        item.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 920px)').matches) {
                mobileSidebarOpen = false;
                updateSidebarState();
            }
        });
    });

    window.addEventListener('resize', updateSidebarState);
    updateClock();
    renderInfoPanel();
    updateSidebarState();
    setInterval(updateClock, 1000);
    panelTimer = window.setInterval(cycleInfoPanel, 2400);
    cargarResumenDashboard();
    window.setInterval(cargarResumenDashboard, 30000);

    const navItems = Array.from(document.querySelectorAll('.top-nav__item'));
    const sections = Array.from(document.querySelectorAll('main .panel-card, main .result-panel, main .status-banner, main .stats-grid'));

    const activateNavItem = (targetId) => {
        navItems.forEach((navItem) => {
            const isActive = navItem.getAttribute('href') === targetId;
            navItem.classList.toggle('active', isActive);
        });
    };

    navItems.forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            const targetId = item.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            if (!targetSection) {
                return;
            }

            activateNavItem(targetId);
            item.classList.add('is-targeted');
            setTimeout(() => item.classList.remove('is-targeted'), 450);

            targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const targetId = `#${entry.target.id}`;
                activateNavItem(targetId);
            }
        });
    }, {
        threshold: 0.25
    });

    sections.forEach((section) => {
        if (section.id) {
            observer.observe(section);
        }
    });

    if (accordionToggle && accordionContent) {
        accordionToggle.addEventListener('click', () => {
            const expanded = accordionToggle.getAttribute('aria-expanded') === 'true';
            accordionToggle.setAttribute('aria-expanded', String(!expanded));
            accordionContent.classList.toggle('is-collapsed', expanded);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', (event) => {
            const [file] = event.target.files;
            updateFileDetails(file);
        });
    }

    if (dropzone) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('drag-over');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('drag-over');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            const [file] = event.dataTransfer.files;
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                updateFileDetails(file);
            }
        });
    }

    if (importBtn) {
        importBtn.addEventListener('click', async () => {
            if (!fileInput.files.length) {
                return;
            }

            if (importBtn.disabled) {
                statusMessage.textContent = 'El archivo no es válido para continuar.';
                statusMessage.style.color = 'var(--danger)';
                return;
            }

            const [file] = fileInput.files;
            const formData = new FormData();
            formData.append('archivo', file);
            formData.append('accion', 'importar');
            console.log('=== IMPORTAR PRESIONADO ===');
            console.log('Acción:', formData.get('accion'));

            importBtn.disabled = true;
            statusMessage.textContent = 'Importando inventario...';
            statusMessage.style.color = '#2563eb';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'No se pudo completar la importación.');
                }

                progressFill.style.width = '100%';
                progressPercent.textContent = '100%';
                statusMessage.textContent = `Se importaron correctamente ${result.registros_importados} registros.`;
                statusMessage.style.color = 'var(--success)';
            } catch (error) {
                statusMessage.textContent = error.message || 'No se pudo completar la importación.';
                statusMessage.style.color = 'var(--danger)';
            } finally {
                importBtn.disabled = false;
            }
        });
    }

    if (verifyBtn && verifierTextarea) {
        verifyBtn.addEventListener('click', async () => {
            const equipos = verifierTextarea.value || '';

            if (!equipos.trim()) {
                renderResultadosVerificador([]);
                return;
            }

            verifyBtn.disabled = true;

            try {
                const body = new URLSearchParams();
                body.set('equipos', equipos);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: body.toString()
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'No se pudo completar la verificación.');
                }

                actualizarTarjetasVerificador(result.data || {});
                renderResultadosVerificador(result.data?.resultados || []);
            } catch (error) {
                renderResultadosVerificador([]);
                console.error(error);
            } finally {
                verifyBtn.disabled = false;
            }
        });
    }

    if (resultsTableBody) {
        resultsTableBody.addEventListener('click', async (event) => {
            const button = event.target.closest('.action-link');
            if (!button) {
                return;
            }

            const row = button.closest('tr');
            const codigo = row?.querySelector('td')?.textContent?.trim() || '';

            if (!codigo) {
                return;
            }

            button.disabled = true;

            try {
                const payload = await solicitarDetalleEquipo(codigo);
                abrirDetalleModal(payload);
            } catch (error) {
                abrirDetalleModal({
                    success: false,
                    message: error?.message || 'No se pudo obtener el detalle del equipo.'
                });
            } finally {
                button.disabled = false;
            }
        });
    }
});
