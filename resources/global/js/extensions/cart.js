/**
 * Cart Manager for Extension Batch Operations
 * Story 3.1: Panier d'extensions - CartDrawer
 *
 * Manages the extension cart with sessionStorage persistence,
 * drawer UI, badge counter with micro-animation, and event delegation.
 */

import { escapeHtml } from './utils.js';

const STORAGE_KEY = 'extension_cart';
const ANIMATION_DURATION_MS = 300;
const BADGE_BOUNCE_MS = 200;
const ALLOWED_URL_PROTOCOLS = ['http:', 'https:'];

/**
 * Get a translation string from the global translations object.
 * @param {string} key
 * @returns {string}
 */
function t(key) {
    return window.__extensionTranslations?.[key] || key;
}

/**
 * Load cart items from sessionStorage.
 * @returns {Array}
 */
function loadCart() {
    try {
        return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
    } catch {
        return [];
    }
}

/**
 * Save cart items to sessionStorage.
 * @param {Array} items
 */
function saveCart(items) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(items));
}

/**
 * Validate and sanitize a URL for use in HTML attributes.
 * Only allows http: and https: protocols to prevent javascript: XSS.
 * @param {string} url
 * @returns {string}
 */
function sanitizeUrl(url) {
    if (!url) return '';
    try {
        const parsed = new URL(url, window.location.origin);
        if (ALLOWED_URL_PROTOCOLS.includes(parsed.protocol)) {
            return parsed.href;
        }
        return '';
    } catch {
        return '';
    }
}

/**
 * CartManager class
 * Handles cart state, drawer rendering, badge counter, and events.
 */
export class CartManager {
    constructor() {
        this.items = loadCart();
        this._focusTrapHandler = null;
        this._bindEvents();
        this.updateBadge();
        this.renderDrawerContent();
    }

    /**
     * Add an extension to the cart.
     * @param {Object} extension - { uuid, name, type, price, thumbnail, hasUpdate }
     * @returns {boolean}
     */
    add(extension) {
        if (this.items.find(item => item.uuid === extension.uuid)) {
            return false;
        }
        this.items.push(extension);
        saveCart(this.items);
        this.updateBadge();
        this.renderDrawerContent();
        document.dispatchEvent(new CustomEvent('extension:cart-add', { detail: extension }));
        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'add', cart: this.items.map(i => i.uuid), count: this.items.length },
        }));
        return true;
    }

    /**
     * Remove an extension from the cart by UUID.
     * @param {string} uuid
     * @returns {boolean}
     */
    remove(uuid) {
        const index = this.items.findIndex(item => item.uuid === uuid);
        if (index === -1) return false;
        const removed = this.items.splice(index, 1)[0];
        saveCart(this.items);
        this.updateBadge();
        this.renderDrawerContent();
        document.dispatchEvent(new CustomEvent('extension:cart-remove', { detail: removed }));
        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'remove', cart: this.items.map(i => i.uuid), count: this.items.length },
        }));
        return true;
    }

    /**
     * Clear all items from the cart.
     */
    clear() {
        this.items = [];
        saveCart(this.items);
        this.updateBadge();
        this.renderDrawerContent();
        document.dispatchEvent(new CustomEvent('extension:cart-clear'));
        document.dispatchEvent(new CustomEvent('extension:cart-updated', {
            detail: { action: 'clear', cart: [], count: 0 },
        }));
    }

    /**
     * Get a copy of all cart items.
     * @returns {Array}
     */
    getItems() {
        return [...this.items];
    }

    /**
     * Get cart item count.
     * @returns {number}
     */
    count() {
        return this.items.length;
    }

    /**
     * Check if an extension is already in cart.
     * @param {string} uuid
     * @returns {boolean}
     */
    has(uuid) {
        return this.items.some(item => item.uuid === uuid);
    }

    /**
     * Open the cart drawer.
     */
    open() {
        const drawer = document.getElementById('cart-drawer');
        const overlay = document.getElementById('cart-overlay');
        if (!drawer) return;

        overlay?.classList.remove('hidden');
        requestAnimationFrame(() => {
            drawer.classList.remove('translate-x-full');
            drawer.classList.add('translate-x-0');
            overlay?.classList.remove('opacity-0');
            overlay?.classList.add('opacity-100');
        });

        // Focus first interactive element after CSS transition completes
        setTimeout(() => {
            const focusable = this._getFocusableElements(drawer);
            if (focusable.length > 0) focusable[0].focus({ preventScroll: true });
        }, ANIMATION_DURATION_MS);

        this._installFocusTrap(drawer);
    }

    /**
     * Close the cart drawer.
     */
    close() {
        const drawer = document.getElementById('cart-drawer');
        const overlay = document.getElementById('cart-overlay');
        if (!drawer) return;

        drawer.classList.add('translate-x-full');
        drawer.classList.remove('translate-x-0');
        overlay?.classList.add('opacity-0');
        overlay?.classList.remove('opacity-100');

        setTimeout(() => {
            overlay?.classList.add('hidden');
        }, ANIMATION_DURATION_MS);

        this._removeFocusTrap();

        // Return focus to the trigger button without scrolling to top
        const triggerBtn = document.querySelector('[data-action="open-cart"]');
        if (triggerBtn) triggerBtn.focus({ preventScroll: true });
    }

    /**
     * Update the cart badge counter with scale bounce micro-animation.
     */
    updateBadge() {
        const badge = document.getElementById('cart-badge');
        if (!badge) return;

        const count = this.items.length;
        badge.textContent = count;

        if (count > 0) {
            badge.classList.remove('hidden');
            badge.style.transition = 'transform 0.2s ease';
            badge.style.transform = 'scale(1.4)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, BADGE_BOUNCE_MS);
        } else {
            badge.classList.add('hidden');
        }

        const cartBtn = document.querySelector('[data-action="open-cart"]');
        if (cartBtn) {
            const suffix = count !== 1 ? 's' : '';
            cartBtn.setAttribute('aria-label',
                `${t('cart_title')}, ${count} extension${suffix}`
            );
        }
    }

    /**
     * Render the cart drawer content (item list, empty state, totals).
     * Uses DOM API for safe rendering and sanitizeUrl for image sources.
     */
    renderDrawerContent() {
        const container = document.getElementById('cart-items');
        const emptyState = document.getElementById('cart-empty');
        const actionsContainer = document.getElementById('cart-actions');
        const totalEl = document.getElementById('cart-total');
        const updateBtn = document.getElementById('cart-update-btn');

        if (!container) return;

        if (this.items.length === 0) {
            container.textContent = '';
            if (emptyState) emptyState.classList.remove('hidden');
            if (actionsContainer) actionsContainer.classList.add('hidden');
            if (totalEl) totalEl.textContent = '';
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');
        if (actionsContainer) actionsContainer.classList.remove('hidden');

        const allUpdates = this.items.every(item => item.hasUpdate);

        // Build cart items using safe DOM API
        const fragment = document.createDocumentFragment();
        this.items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg transition-all duration-200';
            row.setAttribute('data-cart-item', item.uuid);

            const thumbContainer = document.createElement('div');
            thumbContainer.className = 'w-10 h-10 flex-shrink-0 bg-white dark:bg-slate-700 rounded-lg flex items-center justify-center overflow-hidden';

            const thumbnailUrl = sanitizeUrl(item.thumbnail);
            if (thumbnailUrl) {
                const img = document.createElement('img');
                img.src = thumbnailUrl;
                img.className = 'max-w-full max-h-full object-contain';
                img.alt = item.name || '';
                thumbContainer.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-puzzle text-gray-400 dark:text-slate-500';
                thumbContainer.appendChild(icon);
            }

            const info = document.createElement('div');
            info.className = 'flex-1 min-w-0';

            const nameEl = document.createElement('div');
            nameEl.className = 'text-sm font-medium text-gray-900 dark:text-white truncate';
            nameEl.textContent = item.name || '';

            const priceEl = document.createElement('div');
            priceEl.className = 'text-xs text-gray-500 dark:text-gray-400';
            if (item.hasUpdate) {
                const updateIcon = document.createElement('i');
                updateIcon.className = 'bi bi-arrow-up-circle text-amber-500';
                priceEl.appendChild(updateIcon);
                priceEl.appendChild(document.createTextNode(' ' + t('cart_update_label')));
            } else {
                priceEl.textContent = item.price || t('free');
            }

            info.appendChild(nameEl);
            info.appendChild(priceEl);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'flex-shrink-0 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded';
            removeBtn.setAttribute('data-action', 'remove-from-cart');
            removeBtn.setAttribute('data-uuid', item.uuid);
            removeBtn.title = t('cart_remove');
            removeBtn.setAttribute('aria-label', `${t('cart_remove')} - ${item.name || ''}`);

            const removeIcon = document.createElement('i');
            removeIcon.className = 'bi bi-x-lg';
            removeBtn.appendChild(removeIcon);

            row.appendChild(thumbContainer);
            row.appendChild(info);
            row.appendChild(removeBtn);
            fragment.appendChild(row);
        });

        container.textContent = '';
        container.appendChild(fragment);

        if (totalEl) {
            const count = this.items.length;
            totalEl.textContent = `${count} extension${count > 1 ? 's' : ''}`;
        }

        const installBtns = document.getElementById('cart-install-btns');
        if (updateBtn && installBtns) {
            if (allUpdates && this.items.length > 0) {
                updateBtn.classList.remove('hidden');
                installBtns.classList.add('hidden');
            } else {
                updateBtn.classList.add('hidden');
                installBtns.classList.remove('hidden');
            }
        }
    }

    /**
     * Dynamically inject "Add to cart" buttons on extension cards.
     */
    injectCartButtons() {
        const extensionsData = window.__extensionsData;
        if (!extensionsData) return;

        document.querySelectorAll('.extension-item').forEach(card => {
            const name = card.dataset.name;
            const ext = Object.values(extensionsData).find(e => e.name === name);
            if (!ext) return;

            if ((!ext.isInstalled && ext.isActivable) || ext.hasUpdate) {
                const btnContainer = card.querySelector('.flex.flex-col.gap-2');
                if (!btnContainer) return;

                if (btnContainer.querySelector('[data-action="add-to-cart"]')) return;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-indigo-300 dark:border-indigo-600 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors';
                btn.setAttribute('data-action', 'add-to-cart');
                btn.setAttribute('data-uuid', ext.uuid);

                if (this.has(ext.uuid)) {
                    btn.innerHTML = `<i class="bi bi-cart-check"></i> ${escapeHtml(t('cart_in_cart'))}`;
                    btn.classList.add('opacity-60');
                    btn.disabled = true;
                } else {
                    btn.innerHTML = `<i class="bi bi-cart-plus"></i> ${escapeHtml(t('cart_add'))}`;
                }

                btnContainer.appendChild(btn);
            }
        });
    }

    /**
     * Refresh the cart button states after cart changes.
     */
    refreshCartButtons() {
        document.querySelectorAll('[data-action="add-to-cart"]').forEach(btn => {
            const uuid = btn.dataset.uuid;
            if (this.has(uuid)) {
                btn.innerHTML = `<i class="bi bi-cart-check"></i> ${escapeHtml(t('cart_in_cart'))}`;
                btn.classList.add('opacity-60');
                btn.disabled = true;
            } else {
                btn.innerHTML = `<i class="bi bi-cart-plus"></i> ${escapeHtml(t('cart_add'))}`;
                btn.classList.remove('opacity-60');
                btn.disabled = false;
            }
        });
    }

    /**
     * Get all focusable elements within a container.
     * @param {HTMLElement} container
     * @returns {HTMLElement[]}
     */
    _getFocusableElements(container) {
        const selector = [
            'button:not([disabled])',
            '[href]',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
        ].join(', ');
        return Array.from(container.querySelectorAll(selector))
            .filter(el => el.offsetParent !== null);
    }

    /**
     * Install a focus trap that cycles Tab/Shift+Tab within the drawer.
     * Prevents focus from escaping the dialog while open (ARIA requirement).
     * @param {HTMLElement} drawer
     */
    _installFocusTrap(drawer) {
        this._removeFocusTrap();
        this._focusTrapHandler = (e) => {
            if (e.key !== 'Tab') return;
            const focusable = this._getFocusableElements(drawer);
            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };
        document.addEventListener('keydown', this._focusTrapHandler);
    }

    /**
     * Remove the active focus trap handler.
     */
    _removeFocusTrap() {
        if (this._focusTrapHandler) {
            document.removeEventListener('keydown', this._focusTrapHandler);
            this._focusTrapHandler = null;
        }
    }

    /**
     * Bind event listeners using event delegation.
     */
    _bindEvents() {
        document.addEventListener('click', (e) => {
            // Add to cart
            const addBtn = e.target.closest('[data-action="add-to-cart"]');
            if (addBtn) {
                e.preventDefault();
                e.stopPropagation();
                const uuid = addBtn.dataset.uuid;
                const extensionData = window.__extensionsData?.[uuid];
                if (extensionData) {
                    if (this.add(extensionData)) {
                        this.refreshCartButtons();
                        this.open();
                    }
                }
                return;
            }

            // Remove from cart
            const removeBtn = e.target.closest('[data-action="remove-from-cart"]');
            if (removeBtn) {
                e.preventDefault();
                const uuid = removeBtn.dataset.uuid;
                const itemEl = removeBtn.closest('[data-cart-item]');
                if (itemEl) {
                    itemEl.style.transition = 'opacity 0.2s, transform 0.2s';
                    itemEl.style.opacity = '0';
                    itemEl.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        this.remove(uuid);
                        this.refreshCartButtons();
                    }, BADGE_BOUNCE_MS);
                } else {
                    this.remove(uuid);
                    this.refreshCartButtons();
                }
                return;
            }

            // Open cart
            if (e.target.closest('[data-action="open-cart"]')) {
                e.preventDefault();
                this.open();
                return;
            }

            // Close cart (button or overlay click)
            if (e.target.closest('[data-action="close-cart"]')) {
                e.preventDefault();
                this.close();
                return;
            }
            if (e.target.id === 'cart-overlay') {
                this.close();
                return;
            }
        });

        // Escape key to close drawer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const drawer = document.getElementById('cart-drawer');
                if (drawer && !drawer.classList.contains('translate-x-full')) {
                    this.close();
                }
            }
        });

        // Listen for cart changes to refresh buttons
        document.addEventListener('extension:cart-add', () => this.refreshCartButtons());
        document.addEventListener('extension:cart-remove', () => this.refreshCartButtons());
        document.addEventListener('extension:cart-clear', () => this.refreshCartButtons());
    }
}
