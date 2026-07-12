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

    const actualizarTarjetasVerificador = (data) => {
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
                    <td>${escapeHtml(item.ubicacion || 'Sin registro')}</td>
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
});
