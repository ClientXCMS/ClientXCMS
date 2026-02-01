import { post, get } from './ajax.js';
import { ExtensionStore } from './state.js';
import { CartManager } from './cart.js';
import { BatchEngine } from './batch.js';
import { init as initSearch } from './search.js';

/** @type {ExtensionStore} */
let store;

/**
 * Returns the closest .js-extension-card element from an event target.
 * @param {HTMLElement} target
 * @returns {HTMLElement|null}
 */
function getCardFromTarget(target) {
    return target.closest('.js-extension-card');
}

/**
 * Handles click events delegated from extension card containers.
 * Routes events to appropriate handlers based on [data-action] attribute.
 * @param {MouseEvent} event
 */
function handleCardClick(event) {
    const card = getCardFromTarget(event.target);
    if (!card) return;

    const uuid = card.dataset.uuid;
    if (!uuid) return;

    const actionEl = event.target.closest('[data-action]');
    if (!actionEl) return;

    const action = actionEl.dataset.action;

    switch (action) {
        case 'add-to-cart':
            event.preventDefault();
            store.addToCart(uuid);
            break;
        case 'remove-from-cart':
            event.preventDefault();
            store.removeFromCart(uuid);
            break;
        default:
            break;
    }
}

/**
 * Sets up event delegation on all known extension card containers.
 */
function setupEventDelegation() {
    const containers = [
        document.getElementById('extensions-grid'),
        document.getElementById('installed-grid'),
        document.getElementById('themes-grid'),
    ];

    containers.forEach((container) => {
        if (container) {
            container.addEventListener('click', handleCardClick);
        }
    });
}

/**
 * Initializes the extensions module.
 * Creates the store, reads DOM state, and sets up event delegation.
 */
function initialize() {
    store = new ExtensionStore();
    store.init();
    setupEventDelegation();
    initSearch();
}

/**
 * Cart and Batch initialization (Stories 3.x)
 *
 * Dependencies:
 * - window.__extensionsData: JSON object of all extensions (set by Blade)
 * - window.__extensionsRoutes: { base: '/admin/settings/extensions' } (set by Blade)
 */
function initializeCartAndBatch() {
    const cart = new CartManager();
    const batch = new BatchEngine(cart);
    cart.injectCartButtons();
    batch.initUpdateBanner();
    if (import.meta.env.DEV) {
        window.__extensionCart = cart;
        window.__extensionBatch = batch;
    }
}

/**
 * Single initialization entry point for the extensions module.
 */
function boot() {
    initialize();
    initializeCartAndBatch();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { store, post, get };
