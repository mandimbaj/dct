(() => {
    const triggerSelector = [
        '.fi-main-sidebar .fi-sidebar-item-btn',
        '.fi-main-sidebar .fi-sidebar-group-btn',
        '.fi-page-sub-navigation-sidebar .fi-sidebar-item-btn',
        '.fi-page-sub-navigation-sidebar .fi-sidebar-group-btn',
    ].join(',');

    const labelSelector = '.fi-sidebar-item-label, .fi-sidebar-group-label';
    const desktopSidebarMedia = window.matchMedia('(min-width: 1024px) and (hover: hover)');
    const sidebarCollapsedClass = 'aho-sidebar-collapsed';
    const sidebarToggleId = 'aho-sidebar-toggle';

    let pendingTimer = null;

    const cleanText = (value) => value.replace(/\s+/g, ' ').trim();
    const getMainSidebar = () => document.querySelector('.fi-main-sidebar');

    const updateSidebarToggle = () => {
        const toggle = document.getElementById(sidebarToggleId);
        const hasSidebar = Boolean(getMainSidebar());

        if (!toggle) {
            return;
        }

        toggle.hidden = !desktopSidebarMedia.matches || !hasSidebar;

        const isCollapsed = document.body?.classList.contains(sidebarCollapsedClass) || false;
        const label = isCollapsed ? 'Afficher le menu principal' : 'Masquer le menu principal';

        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('aria-expanded', String(!isCollapsed));
        toggle.setAttribute('title', label);
    };

    const setSidebarCollapsed = (isCollapsed) => {
        if (!desktopSidebarMedia.matches || !getMainSidebar()) {
            document.body?.classList.remove(sidebarCollapsedClass);
            updateSidebarToggle();
            return;
        }

        document.body?.classList.toggle(sidebarCollapsedClass, isCollapsed);

        updateSidebarToggle();
    };

    const syncToggleableSidebar = () => {
        if (!desktopSidebarMedia.matches) {
            document.body?.classList.remove(sidebarCollapsedClass);
            updateSidebarToggle();
            return;
        }

        setSidebarCollapsed(false);
    };

    const ensureSidebarToggle = () => {
        let toggle = document.getElementById(sidebarToggleId);

        if (!toggle) {
            toggle = document.createElement('button');
            toggle.id = sidebarToggleId;
            toggle.className = 'aho-sidebar-toggle';
            toggle.type = 'button';
            document.body?.appendChild(toggle);

            toggle.addEventListener('click', () => {
                const isCollapsed = document.body?.classList.contains(sidebarCollapsedClass) || false;
                setSidebarCollapsed(!isCollapsed);
            });
        }

        syncToggleableSidebar();
    };

    const refreshTooltips = () => {
        document.querySelectorAll(triggerSelector).forEach((trigger) => {
            const label = trigger.querySelector(labelSelector);
            const text = cleanText(label?.textContent || trigger.textContent || '');

            if (!text) {
                return;
            }

            trigger.setAttribute('title', text);
            trigger.setAttribute('aria-label', text);
        });
    };

    const scheduleRefresh = () => {
        if (pendingTimer) {
            window.clearTimeout(pendingTimer);
        }

        pendingTimer = window.setTimeout(() => {
            pendingTimer = null;
            refreshTooltips();
        }, 0);
    };

    document.addEventListener('DOMContentLoaded', scheduleRefresh);
    document.addEventListener('livewire:navigated', () => {
        ensureSidebarToggle();
        scheduleRefresh();
    });
    window.addEventListener('resize', () => {
        syncToggleableSidebar();
        scheduleRefresh();
    });

    if (desktopSidebarMedia.addEventListener) {
        desktopSidebarMedia.addEventListener('change', syncToggleableSidebar);
    }

    new MutationObserver(scheduleRefresh).observe(document.documentElement, {
        childList: true,
        subtree: true,
    });

    scheduleRefresh();
    ensureSidebarToggle();
})();
