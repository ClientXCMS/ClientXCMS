import { post, get } from './ajax.js';
import { ExtensionStore } from './state.js';
import { CartManager } from './cart.js';
import { BatchEngine } from './batch.js';
import { init as initSearch } from './search.js';
import { bindActionHandlers } from './ajax-handlers.js';
import { initModal } from './modal.js';

/** @type {ExtensionStore} */
let store;

/**
 * Initializes the extensions module.
 * Creates the store, reads DOM state, and sets up search.
 */
function initialize() {
    store = new ExtensionStore();
    store.init();
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
    // Clean up legacy cart key from ExtensionStore (now handled by CartManager only)
    try {
        sessionStorage.removeItem('clientxcms-extension-cart');
    } catch {
        /* sessionStorage may be unavailable */
    }

    initialize();
    bindActionHandlers(document);
    initModal();
    initializeCartAndBatch();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { store, post, get };
