(() => {
    'use strict';

    const buttons = () => Array.from(document.querySelectorAll('[data-pwa-install-button]'));
    let installPrompt = null;

    const isStandalone = () => (
        window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
    );

    const hideButtons = () => buttons().forEach((button) => {
        button.hidden = true;
        button.disabled = true;
    });

    const showButtons = () => buttons().forEach((button) => {
        button.hidden = false;
        button.disabled = false;
    });

    document.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) return;
        const button = event.target.closest('[data-pwa-install-button]');
        if (!button || !installPrompt || isStandalone()) return;

        const promptEvent = installPrompt;
        button.disabled = true;
        try {
            await promptEvent.prompt();
            await promptEvent.userChoice;
            installPrompt = null;
        } catch (error) {
            installPrompt = null;
            console.error('No fue posible mostrar la instalación de VASCOR OPS.', error);
        } finally {
            if (installPrompt && !isStandalone()) {
                showButtons();
            } else {
                hideButtons();
            }
        }
    });

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        if (!isStandalone()) showButtons();
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        hideButtons();
    });

    window.addEventListener('DOMContentLoaded', () => {
        if (isStandalone() || !installPrompt) {
            hideButtons();
        } else {
            showButtons();
        }
    }, { once: true });
})();
