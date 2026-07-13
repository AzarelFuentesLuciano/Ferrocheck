document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('fileInput');
    const dropzone = document.getElementById('dropzone');
    const fileInfo = document.getElementById('fileInfo');
    const importBtn = document.getElementById('importBtn');
    const verifyBtn = document.getElementById('verifyBtn');
    const verifierTextarea = document.getElementById('verifierTextarea');
    const resultsTableBody = document.querySelector('.results-table tbody');
    const statusMessage = document.getElementById('statusMessage');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const recordCount = document.getElementById('recordCount');
    const fileStatus = document.getElementById('fileStatus');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    const currentDate = document.getElementById('currentDate');
    const currentTime = document.getElementById('currentTime');
    const lastImport = document.getElementById('lastImportValue');
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    const loader = document.getElementById('globalLoader');
    const toastContainer = document.getElementById('toastContainer');

    const kpis = {
        cantidadRegistros: document.getElementById('kpiCantidadRegistros'),
        totalPlataformas: document.getElementById('kpiTotalPlataformas'),
        totalFerromex: document.getElementById('kpiTotalFerromex'),
        totalKansas: document.getElementById('kpiTotalKansas'),
        servidor: document.getElementById('kpiServidor'),
        baseDatos: document.getElementById('kpiBaseDatos')
    };

    const formatFileSize = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 Bytes';
        }
        const units = ['Bytes', 'KB', 'MB', 'GB'];
        const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${(bytes / Math.pow(1024, unitIndex)).toFixed(2)} ${units[unitIndex]}`;
    };

    const showLoader = (text = 'Procesando...') => {
        if (!loader) {
            return;
        }
        const label = loader.querySelector('[data-loader-text]');
        if (label) {
            label.textContent = text;
        }
        loader.classList.add('is-visible');
    };

    const hideLoader = () => {
        loader?.classList.remove('is-visible');
    };

    const showToast = (message, tone = 'info') => {
        if (!toastContainer) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${tone}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 3200);
    };

    const setProgress = (value, text) => {
        const percent = Math.max(0, Math.min(100, value));
        if (progressFill) {
            progressFill.style.width = `${percent}%`;
        }
        if (progressPercent) {
            progressPercent.textContent = `${percent}%`;
        }
        if (statusMessage && text) {
            statusMessage.textContent = text;
        }
    };

    const setStatusColor = (tone) => {
        if (!statusMessage) {
            return;
        }
        statusMessage.classList.remove('status-success', 'status-error', 'status-info');
        statusMessage.classList.add(`status-${tone}`);
    };

    const formatearFecha = (valor) => {
        if (!valor) {
            return 'Sin registros';
        }

        const date = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return 'Sin registros';
        }

        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).format(date);
    };

    const actualizarReloj = () => {
        const now = new Date();
        if (currentDate) {
            currentDate.textContent = now.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'long',
                day: '2-digit'
            });
        }
        if (currentTime) {
            currentTime.textContent = now.toLocaleTimeString('es-MX');
        }
    };

    const setKpiSkeleton = (active) => {
        Object.values(kpis).forEach((item) => {
            if (!item) {
                return;
            }
            item.closest('.kpi-card')?.classList.toggle('is-loading', active);
        });
    };

    const actualizarDashboard = async () => {
        setKpiSkeleton(true);

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

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'No se pudo obtener el dashboard.');
            }

            const data = payload.data || {};
            if (kpis.cantidadRegistros) {
                kpis.cantidadRegistros.textContent = String(data.cantidad_registros ?? 0);
            }
            if (kpis.totalPlataformas) {
                kpis.totalPlataformas.textContent = String(data.total_plataformas ?? 0);
            }
            if (kpis.totalFerromex) {
                kpis.totalFerromex.textContent = String(data.total_ferromex ?? 0);
            }
            if (kpis.totalKansas) {
                kpis.totalKansas.textContent = String(data.total_kansas ?? 0);
            }
            if (kpis.servidor) {
                kpis.servidor.textContent = String(data.estado_servidor ?? 'En linea');
            }
            if (kpis.baseDatos) {
                kpis.baseDatos.textContent = String(data.estado_base_datos ?? 'Conectada');
            }
            if (lastImport) {
                lastImport.textContent = formatearFecha(data.ultima_actualizacion ?? null);
            }
        } catch (error) {
            showToast(error.message || 'No se pudo actualizar el dashboard.', 'error');
        } finally {
            setKpiSkeleton(false);
        }
    };

    const resetImportState = () => {
        if (recordCount) {
            recordCount.textContent = '0';
        }
        if (fileStatus) {
            fileStatus.textContent = 'Pendiente';
        }
        setProgress(0, 'Listo para analizar archivo');
        setStatusColor('info');
        if (importBtn) {
            importBtn.disabled = true;
        }
    };

    const analizarArchivo = async (file) => {
        if (!file) {
            return;
        }

        showLoader('Analizando archivo oficial...');
        setProgress(30, 'Validando encabezados...');
        setStatusColor('info');

        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('accion', 'analizar');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'No fue posible analizar el archivo.');
        }

        if (recordCount) {
            recordCount.textContent = String(result.registros ?? 0);
        }
        if (fileStatus) {
            fileStatus.textContent = result.estado || 'No valido';
        }

        if (result.valido) {
            setProgress(100, 'Archivo valido. Listo para importar.');
            setStatusColor('success');
            importBtn.disabled = false;
            showToast('Archivo validado correctamente.', 'success');
        } else {
            setProgress(100, result.mensaje || 'Archivo no valido.');
            setStatusColor('error');
            importBtn.disabled = true;
            showToast(result.mensaje || 'Archivo no valido.', 'error');
        }
    };

    const importarArchivo = async () => {
        if (!fileInput?.files?.length) {
            showToast('Selecciona un archivo primero.', 'warning');
            return;
        }

        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('accion', 'importar');

        importBtn.disabled = true;
        showLoader('Importando inventario...');
        setProgress(70, 'Insertando registros en base de datos...');
        setStatusColor('info');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'No se pudo completar la importacion.');
            }

            setProgress(100, `Importacion exitosa: ${result.registros_importados} registros.`);
            setStatusColor('success');
            showToast('Importacion completada correctamente.', 'success');
            await actualizarDashboard();
        } catch (error) {
            setStatusColor('error');
            setProgress(100, error.message || 'No se pudo importar.');
            showToast(error.message || 'No se pudo importar.', 'error');
        } finally {
            hideLoader();
            importBtn.disabled = false;
        }
    };

    const estadoTexto = (estado) => {
        if (estado === 'EN_ENCANTADA') {
            return 'EN ENCANTADA';
        }
        if (estado === 'OTRA_UBICACION') {
            return 'OTRA UBICACION';
        }
        return 'NO ENCONTRADO';
    };

    const estadoClass = (estado) => {
        if (estado === 'EN_ENCANTADA') {
            return 'status-encantada';
        }
        if (estado === 'OTRA_UBICACION') {
            return 'status-otra';
        }
        return 'status-no-encontrado';
    };

    const renderResultados = (resultados) => {
        if (!resultsTableBody) {
            return;
        }

        if (!Array.isArray(resultados) || resultados.length === 0) {
            resultsTableBody.innerHTML = '<tr><td colspan="7">No hay resultados para mostrar.</td></tr>';
            return;
        }

        resultsTableBody.innerHTML = resultados.map((item) => {
            return `
                <tr>
                    <td>${String(item.codigo || '')}</td>
                    <td>${String(item.transportista || '—')}</td>
                    <td>${String(item.ubicacion || 'Sin registro')}</td>
                    <td><span class="status-pill ${estadoClass(item.estado)}">${estadoTexto(item.estado)}</span></td>
                    <td>${String(item.ultima_actualizacion || '—')}</td>
                    <td>${String(item.evidencia || '—')}</td>
                    <td><button class="action-link" type="button">Ver</button></td>
                </tr>
            `;
        }).join('');
    };

    const verificarEquipos = async () => {
        const equipos = verifierTextarea?.value || '';

        if (!equipos.trim()) {
            renderResultados([]);
            showToast('Ingresa al menos un codigo para verificar.', 'warning');
            return;
        }

        verifyBtn.disabled = true;
        showLoader('Verificando plataformas...');

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
                throw new Error(result.message || 'No se pudo completar la verificacion.');
            }

            renderResultados(result.data?.resultados || []);
            showToast('Verificacion completada.', 'success');
        } catch (error) {
            renderResultados([]);
            showToast(error.message || 'No se pudo verificar.', 'error');
        } finally {
            verifyBtn.disabled = false;
            hideLoader();
        }
    };

    const hydrateFileInfo = (file) => {
        if (!fileInfo || !fileName || !fileSize || !fileType) {
            return;
        }

        fileInfo.hidden = false;
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileType.textContent = file.type || 'Desconocido';
    };

    const onFileSelected = async (file) => {
        if (!file) {
            resetImportState();
            return;
        }

        hydrateFileInfo(file);

        try {
            await analizarArchivo(file);
        } catch (error) {
            setStatusColor('error');
            setProgress(100, error.message || 'No fue posible analizar.');
            showToast(error.message || 'No fue posible analizar.', 'error');
        } finally {
            hideLoader();
        }
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
            menuToggle.setAttribute('aria-expanded', String(mobileSidebarOpen));
            return;
        }

        sidebar.classList.toggle('is-collapsed', desktopCollapsed);
        sidebar.classList.remove('is-open');
        sidebarBackdrop?.classList.remove('is-visible');
        menuToggle.setAttribute('aria-expanded', String(!desktopCollapsed));
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

    window.addEventListener('resize', updateSidebarState);

    fileInput?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        await onFileSelected(file);
    });

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

        dropzone.addEventListener('drop', async (event) => {
            event.preventDefault();
            const file = event.dataTransfer?.files?.[0];
            if (!file || !fileInput) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            await onFileSelected(file);
        });
    }

    importBtn?.addEventListener('click', importarArchivo);
    verifyBtn?.addEventListener('click', verificarEquipos);

    atualizarTodo();

    async function atualizarTodo() {
        resetImportState();
        updateSidebarState();
        actualizarReloj();
        await actualizarDashboard();
        window.setInterval(actualizarReloj, 1000);
        window.setInterval(actualizarDashboard, 30000);
    }
});
