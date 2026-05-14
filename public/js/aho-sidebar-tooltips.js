(() => {
    const triggerSelector = [
        '.fi-main-sidebar .fi-sidebar-item-btn',
        '.fi-main-sidebar .fi-sidebar-group-btn',
        '.fi-page-sub-navigation-sidebar .fi-sidebar-item-btn',
        '.fi-page-sub-navigation-sidebar .fi-sidebar-group-btn',
    ].join(',');

    const labelSelector = '.fi-sidebar-item-label, .fi-sidebar-group-label';

    let pendingTimer = null;

    const cleanText = (value) => value.replace(/\s+/g, ' ').trim();

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
    document.addEventListener('livewire:navigated', scheduleRefresh);
    window.addEventListener('resize', scheduleRefresh);

    new MutationObserver(scheduleRefresh).observe(document.documentElement, {
        childList: true,
        subtree: true,
    });

    scheduleRefresh();
})();
