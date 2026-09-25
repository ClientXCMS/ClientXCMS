const dedupeOverlayInstances = () => {
    const seen = new Set();

    window.$hsOverlayCollection = (window.$hsOverlayCollection || []).filter((instance) => {
        const id = instance?.element?.overlay?.id;
        if (!id) return true;
        if (seen.has(id)) return false;
        seen.add(id);

        return true;
    });
};

const bindExpandedState = () => {
    document.querySelectorAll('[data-hs-overlay][aria-controls]').forEach((trigger) => {
        const overlay = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!overlay || trigger.dataset.overlayState) return;

        trigger.dataset.overlayState = 'bound';
        const sync = () => trigger.setAttribute('aria-expanded', overlay.classList.contains('open') ? 'true' : 'false');

        sync();
        new MutationObserver(sync).observe(overlay, { attributes: true, attributeFilter: ['class'] });
    });
};

bindExpandedState();
window.addEventListener('load', () => {
    dedupeOverlayInstances();
    bindExpandedState();
});
