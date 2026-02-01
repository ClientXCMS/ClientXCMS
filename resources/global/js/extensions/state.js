const CART_STORAGE_KEY = 'clientxcms-extension-cart';

/**
 * ExtensionStore manages extension state using a Map keyed by UUID.
 * Emits CustomEvents on document for state changes.
 * Persists cart state in sessionStorage.
 */
export class ExtensionStore {
    /** @type {Map<string, Object>} */
    #extensions = new Map();

    /** @type {Set<string>} */
    #cart = new Set();

    constructor() {
        this.#restoreCart();
    }

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

    /**
     * Adds an extension UUID to the cart and persists to sessionStorage.
     * @param {string} uuid
     */
    addToCart(uuid) {
        if (!this.#extensions.has(uuid)) return;
        this.#cart.add(uuid);
        this.#persistCart();

        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'add', uuid, cart: this.getCart() },
        }));
    }

    /**
     * Removes an extension UUID from the cart and persists to sessionStorage.
     * @param {string} uuid
     */
    removeFromCart(uuid) {
        this.#cart.delete(uuid);
        this.#persistCart();

        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'remove', uuid, cart: this.getCart() },
        }));
    }

    /**
     * Returns the current cart as an array of UUIDs.
     * @returns {string[]}
     */
    getCart() {
        return Array.from(this.#cart);
    }

    /**
     * Clears all items from the cart and persists the empty state.
     */
    clearCart() {
        this.#cart.clear();
        this.#persistCart();

        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'clear', uuid: null, cart: [] },
        }));
    }

    /**
     * Persists the cart to sessionStorage.
     */
    #persistCart() {
        try {
            sessionStorage.setItem(
                CART_STORAGE_KEY,
                JSON.stringify(this.getCart())
            );
        } catch {
            /* sessionStorage may be unavailable */
        }
    }

    /**
     * Restores the cart from sessionStorage.
     */
    #restoreCart() {
        try {
            const stored = sessionStorage.getItem(CART_STORAGE_KEY);
            if (stored) {
                const uuids = JSON.parse(stored);
                if (Array.isArray(uuids)) {
                    uuids.forEach((uuid) => this.#cart.add(uuid));
                }
            }
        } catch {
            /* ignore parse or storage errors */
        }
    }
}
