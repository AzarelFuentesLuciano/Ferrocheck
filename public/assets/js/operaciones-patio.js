document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    const platformItems = Array.from(document.querySelectorAll('.yard-platform'));
    const detailPanel = document.getElementById('patioDetailPanel');
    const detailTitle = document.getElementById('detailPlatformTitle');
    const detailState = document.getElementById('detailPlatformState');
    const detailObservation = document.getElementById('detailObservation');
    const evidencePreview = document.getElementById('evidencePreview');
    const saveBtn = document.getElementById('btnGuardarOperacion');
    const closeBtn = document.getElementById('btnCerrarOperacion');
    const exportBtn = document.getElementById('btnExportarPdf');
    const cancelBtn = document.getElementById('btnCancelarPlataforma');
    const toastContainer = document.getElementById('toastContainer');

    const stateLabels = {
        libre: 'Libre',
        ocupada: 'Ocupada',
        facturada: 'Facturada',
        cancelada: 'Cancelada',
        con_evidencia: 'Con evidencia',
        sin_evidencia: 'Sin evidencia',
        verificada: 'Verificada',
        pendiente: 'Pendiente'
    };

    let selectedPlatform = null;
    let desktopCollapsed = false;
    let mobileSidebarOpen = false;

    const showToast = (message, tone = 'info') => {
        if (!toastContainer) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${tone}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('is-visible'));
        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 3000);
    };

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

    const clearSelection = () => {
        platformItems.forEach((item) => item.classList.remove('is-selected'));
    };

    const renderDetail = (platform) => {
        if (!detailPanel || !detailTitle || !detailState || !detailObservation || !evidencePreview) {
            return;
        }

        if (!platform) {
            detailPanel.classList.add('is-empty');
            detailTitle.textContent = 'Selecciona una plataforma';
            detailState.textContent = 'Sin seleccion';
            detailObservation.value = '';
            evidencePreview.textContent = 'Sin evidencia cargada';
            return;
        }

        detailPanel.classList.remove('is-empty');
        detailTitle.textContent = `${platform.dataset.zone} · ${platform.dataset.name}`;
        detailState.textContent = stateLabels[platform.dataset.state] || 'Pendiente';
        detailObservation.value = platform.dataset.note || '';
        evidencePreview.textContent = platform.dataset.evidence === '1'
            ? 'Evidencia adjunta en esta plataforma.'
            : 'Sin evidencia cargada';
    };

    platformItems.forEach((platform) => {
        platform.addEventListener('click', () => {
            selectedPlatform = platform;
            clearSelection();
            platform.classList.add('is-selected');
            renderDetail(platform);
        });
    });

    detailObservation?.addEventListener('input', () => {
        if (selectedPlatform) {
            selectedPlatform.dataset.note = detailObservation.value;
        }
    });

    saveBtn?.addEventListener('click', () => {
        if (!selectedPlatform) {
            showToast('Selecciona una plataforma antes de guardar.', 'warning');
            return;
        }

        showToast('Operacion guardada en modo preview.', 'success');
    });

    closeBtn?.addEventListener('click', () => {
        if (!selectedPlatform) {
            showToast('No hay plataforma seleccionada.', 'warning');
            return;
        }

        selectedPlatform.dataset.state = 'verificada';
        selectedPlatform.className = 'yard-platform state-verificada is-selected';
        renderDetail(selectedPlatform);
        showToast('Operacion cerrada en modo preview.', 'success');
    });

    exportBtn?.addEventListener('click', () => {
        showToast('Exportacion PDF preparada para fase backend.', 'info');
    });

    cancelBtn?.addEventListener('click', () => {
        if (!selectedPlatform) {
            showToast('No hay plataforma seleccionada.', 'warning');
            return;
        }

        selectedPlatform.dataset.state = 'cancelada';
        selectedPlatform.className = 'yard-platform state-cancelada is-selected';
        renderDetail(selectedPlatform);
        showToast('Plataforma cancelada en modo preview.', 'error');
    });

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

    renderDetail(null);
    updateSidebarState();
});
