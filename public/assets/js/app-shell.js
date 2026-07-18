(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const shell = document.querySelector('[data-app-shell]');
        const sidebar = document.querySelector('[data-app-shell-sidebar]');
        const toggle = document.querySelector('[data-app-shell-toggle]');
        const toggleIcon = document.querySelector('[data-app-shell-toggle-icon]');
        const backdrop = document.querySelector('[data-app-shell-backdrop]');
        const dateNode = document.querySelector('[data-app-shell-date]');
        const timeNode = document.querySelector('[data-app-shell-time]');
        const mobileQuery = window.matchMedia('(max-width: 920px)');

        if (!shell || !sidebar || !toggle) {
            return;
        }

        let mobileOpen = false;
        let desktopCollapsed = false;

        const updateSidebar = () => {
            if (mobileQuery.matches) {
                shell.classList.remove('is-sidebar-collapsed');
                sidebar.classList.toggle('is-open', mobileOpen);
                backdrop?.classList.toggle('is-visible', mobileOpen);
                document.body.classList.toggle('app-shell-page--locked', mobileOpen);
                toggle.setAttribute('aria-expanded', String(mobileOpen));
                if (toggleIcon) toggleIcon.textContent = mobileOpen ? '✕' : '☰';
                return;
            }

            mobileOpen = false;
            sidebar.classList.remove('is-open');
            backdrop?.classList.remove('is-visible');
            document.body.classList.remove('app-shell-page--locked');
            shell.classList.toggle('is-sidebar-collapsed', desktopCollapsed);
            toggle.setAttribute('aria-expanded', String(!desktopCollapsed));
            if (toggleIcon) toggleIcon.textContent = '☰';
        };

        const closeMobileSidebar = () => {
            if (!mobileQuery.matches || !mobileOpen) return;
            mobileOpen = false;
            updateSidebar();
            toggle.focus();
        };

        const updateClock = () => {
            const now = new Date();
            if (dateNode) {
                dateNode.textContent = now.toLocaleDateString('es-MX', {
                    day: '2-digit', month: 'long', year: 'numeric',
                });
            }
            if (timeNode) timeNode.textContent = now.toLocaleTimeString('es-MX');
        };

        toggle.addEventListener('click', () => {
            if (mobileQuery.matches) {
                mobileOpen = !mobileOpen;
            } else {
                desktopCollapsed = !desktopCollapsed;
            }
            updateSidebar();
        });

        backdrop?.addEventListener('click', closeMobileSidebar);

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (mobileQuery.matches) {
                    mobileOpen = false;
                    updateSidebar();
                }
            });
        });

        sidebar.querySelectorAll('[data-app-shell-submenu-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const controlledId = button.getAttribute('aria-controls');
                const submenu = controlledId ? document.getElementById(controlledId) : null;
                if (!submenu) return;
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!expanded));
                submenu.hidden = expanded;
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMobileSidebar();
        });

        const handleBreakpointChange = () => {
            mobileOpen = false;
            updateSidebar();
        };

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', handleBreakpointChange);
        } else {
            mobileQuery.addListener(handleBreakpointChange);
        }

        updateClock();
        window.setInterval(updateClock, 1000);
        updateSidebar();
    });
})();
