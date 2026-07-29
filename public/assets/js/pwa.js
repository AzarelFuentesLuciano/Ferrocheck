(() => {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    const script = document.currentScript
        || document.querySelector('script[data-service-worker-url]');
    const configuredWorkerUrl = script?.dataset.serviceWorkerUrl || '';
    const configuredBaseUrl = script?.dataset.pwaBaseUrl || '';
    const fallbackBaseUrl = new URL('../../', script?.src || document.baseURI);
    const pwaBaseUrl = configuredBaseUrl
        ? new URL(configuredBaseUrl, document.baseURI)
        : fallbackBaseUrl;
    const serviceWorkerUrl = configuredWorkerUrl
        ? new URL(configuredWorkerUrl, document.baseURI)
        : new URL('service-worker.js', pwaBaseUrl);
    const serviceWorkerScope = pwaBaseUrl.pathname;
    const reloadGuardKey = 'vascor-pwa-controller-reload';
    const splashGuardKey = 'vascor-pwa-splash-shown';
    const updateCheckKey = 'vascor-pwa-last-update-check';
    const updateCheckInterval = 30 * 60 * 1000;
    const activationTimeoutMs = 12000;
    let updateNotice = null;
    let reloadRequested = false;
    let updateActivationRequested = false;
    let activationTimeout = null;
    let memoryLastUpdateCheck = 0;

    const safeSessionGet = (key) => {
        try {
            return window.sessionStorage.getItem(key);
        } catch (error) {
            return null;
        }
    };

    const safeSessionSet = (key, value) => {
        try {
            window.sessionStorage.setItem(key, value);
            return true;
        } catch (error) {
            return false;
        }
    };

    const safeSessionRemove = (key) => {
        try {
            window.sessionStorage.removeItem(key);
        } catch (error) {
            // The in-memory guards remain available when storage is blocked.
        }
    };

    if (safeSessionGet(reloadGuardKey) === '1') {
        safeSessionRemove(reloadGuardKey);
    }

    const isStandalone = () => (
        window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
    );

    const showSplash = () => {
        if (!isStandalone() || safeSessionGet(splashGuardKey) === '1') return;
        safeSessionSet(splashGuardKey, '1');
        const splash = document.createElement('div');
        splash.className = 'pwa-splash';
        splash.setAttribute('role', 'status');
        splash.setAttribute('aria-label', 'Iniciando VASCOR OPS');
        splash.innerHTML = '<strong><span>VASCOR</span><span>OPS</span></strong>';
        document.body.append(splash);
        window.setTimeout(() => {
            splash.classList.add('pwa-splash--closing');
            window.setTimeout(() => splash.remove(), 260);
        }, 850);
    };

    const removeUpdateNotice = () => {
        updateNotice?.remove();
        updateNotice = null;
    };

    const waitForWaitingWorker = async (currentRegistration, attempts = 16) => {
        for (let attempt = 0; attempt < attempts; attempt += 1) {
            if (currentRegistration.waiting) return currentRegistration.waiting;
            await new Promise((resolve) => window.setTimeout(resolve, 250));
        }
        return null;
    };

    const restoreUpdateButton = (message) => {
        if (!updateNotice) return;
        const button = updateNotice.querySelector('.pwa-update-notice__button');
        const error = updateNotice.querySelector('.pwa-update-notice__error');
        if (button) {
            button.disabled = false;
            button.textContent = 'Actualizar ahora';
        }
        if (error) {
            error.textContent = message;
            error.hidden = false;
        }
    };

    const fetchVersionMetadata = async () => {
        try {
            const response = await fetch(new URL('pwa-version.json', pwaBaseUrl), {
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!response.ok) return '';
            const metadata = await response.json();
            return metadata.version ? `Versión ${String(metadata.version)}` : '';
        } catch (error) {
            return '';
        }
    };

    const showUpdateNotice = async (currentRegistration) => {
        const waitingWorker = currentRegistration.waiting
            || await waitForWaitingWorker(currentRegistration);
        if (!waitingWorker || !navigator.serviceWorker.controller || updateNotice) return;

        const versionLabel = await fetchVersionMetadata();
        const notice = document.createElement('section');
        notice.className = 'pwa-update-notice';
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', 'polite');
        notice.innerHTML = `
            <span class="pwa-update-notice__icon" aria-hidden="true">↻</span>
            <div class="pwa-update-notice__content">
                <strong class="pwa-update-notice__title">Hay una nueva versión disponible</strong>
                <span class="pwa-update-notice__description">Actualiza VASCOR OPS para obtener los cambios más recientes.</span>
                <span class="pwa-update-notice__version">${versionLabel}</span>
                <span class="pwa-update-notice__error" hidden></span>
            </div>
            <button class="pwa-update-notice__button" type="button">Actualizar ahora</button>
            <button class="pwa-update-notice__close" type="button" aria-label="Cerrar aviso de actualización">×</button>
        `;
        updateNotice = notice;
        document.body.append(notice);

        const updateButton = notice.querySelector('.pwa-update-notice__button');
        const errorMessage = notice.querySelector('.pwa-update-notice__error');
        notice.querySelector('.pwa-update-notice__close')?.addEventListener('click', removeUpdateNotice);
        updateButton?.addEventListener('click', async () => {
            updateButton.disabled = true;
            updateButton.textContent = 'Actualizando…';
            errorMessage.hidden = true;
            try {
                const worker = currentRegistration.waiting
                    || await waitForWaitingWorker(currentRegistration);
                if (!worker) throw new Error('No waiting worker');

                updateActivationRequested = true;
                worker.postMessage({ type: 'SKIP_WAITING' });
                activationTimeout = window.setTimeout(() => {
                    updateActivationRequested = false;
                    activationTimeout = null;
                    restoreUpdateButton('No fue posible aplicar la actualización. Intenta nuevamente.');
                }, activationTimeoutMs);
            } catch (error) {
                updateActivationRequested = false;
                restoreUpdateButton('No fue posible aplicar la actualización. Intenta nuevamente.');
            }
        });
    };

    const watchRegistration = (currentRegistration) => {
        if (currentRegistration.waiting) void showUpdateNotice(currentRegistration);
        currentRegistration.addEventListener('updatefound', () => {
            const installingWorker = currentRegistration.installing;
            if (!installingWorker) return;
            installingWorker.addEventListener('statechange', () => {
                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    void showUpdateNotice(currentRegistration);
                }
            });
        });
    };

    const shouldCheckForUpdate = () => {
        const now = Date.now();
        const stored = Number(safeSessionGet(updateCheckKey) || 0);
        const lastCheck = Math.max(stored, memoryLastUpdateCheck);
        if (now - lastCheck < updateCheckInterval) return false;
        memoryLastUpdateCheck = now;
        safeSessionSet(updateCheckKey, String(now));
        return true;
    };

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!updateActivationRequested || reloadRequested) return;
        updateActivationRequested = false;
        reloadRequested = true;
        if (activationTimeout !== null) {
            window.clearTimeout(activationTimeout);
            activationTimeout = null;
        }
        safeSessionSet(reloadGuardKey, '1');
        window.location.reload();
    });

    window.addEventListener('DOMContentLoaded', showSplash, { once: true });
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register(serviceWorkerUrl, {
                scope: serviceWorkerScope
            });
            watchRegistration(registration);
            if (shouldCheckForUpdate()) {
                await registration.update();
            }
            if (registration.waiting) {
                await showUpdateNotice(registration);
            }
        } catch (error) {
            console.error('No fue posible iniciar la PWA de VASCOR OPS.', error);
        }
    }, { once: true });
})();
