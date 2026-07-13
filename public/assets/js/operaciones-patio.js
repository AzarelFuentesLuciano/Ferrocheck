document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

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

    window.addEventListener('resize', updateSidebarState);
    updateSidebarState();
});
