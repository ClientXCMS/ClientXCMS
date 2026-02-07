/**
 * ExtensionStore manages extension metadata using a Map keyed by UUID.
 * Emits CustomEvents on document for state changes.
 *
 * Cart management is handled exclusively by CartManager (cart.js).
 */
export class ExtensionStore {
    /** @type {Map<string, Object>} */
    #extensions = new Map();

    /**
     * Initializes the store from DOM elements with .js-extension-card class.
     * Reads data-extension-* attributes from each card element.
     */
    init() {
        const cards = document.querySelectorAll('.js-extension-card');
        cards.forEach((card) => {
            const uuid = card.dataset.uuid;
            if (!uuid) return;

            if (!this.#extensions.has(uuid)) {
                this.#extensions.set(uuid, {
                    uuid,
                    type: card.dataset.type || '',
                    status: card.dataset.status || '',
                    enabled: card.dataset.enabled === 'true',
                    version: card.dataset.version || '',
                    hasUpdate: card.dataset.hasUpdate === 'true',
                    activable: card.dataset.activable === 'true',
                });
            }
        });
    }

    /**
     * Returns the extension data for a given UUID.
     * @param {string} uuid
     * @returns {Object|undefined}
     */
    getExtension(uuid) {
        return this.#extensions.get(uuid);
    }

    /**
     * Updates the status of an extension and emits a status-changed event.
     * @param {string} uuid
     * @param {string} newStatus
     */
    updateStatus(uuid, newStatus) {
        const ext = this.#extensions.get(uuid);
        if (!ext) return;

        const oldStatus = ext.status;
        ext.status = newStatus;
        ext.enabled = newStatus === 'enabled';

        document.dispatchEvent(new CustomEvent('extension:status-changed', {
            detail: { uuid, oldStatus, newStatus, extension: { ...ext } },
        }));
    }
}
