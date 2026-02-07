/**
 * Shared utilities for the extensions module.
 * Single source of truth for helpers used across ajax, search, cart, batch, modal.
 */

const ALLOWED_URL_PROTOCOLS = ['http:', 'https:'];
const FOCUSABLE_SELECTOR = [
    'button:not([disabled])',
    'a[href]',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

/**
 * Escape HTML entities for safe rendering inside innerHTML and attributes.
 * Covers: &, <, >, ", ' to prevent XSS in both element content and attributes.
 * @param {string} str
 * @returns {string}
 */
export function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Returns a translated string from the global translations object.
 * @param {string} key - Translation key
 * @param {string} fallback - Fallback text if key is missing
 * @returns {string}
 */
export function getTranslation(key, fallback) {
    return (window.__extensionTranslations && window.__extensionTranslations[key]) || fallback;
}

/**
 * Replace :placeholder tokens in a translation string.
 * @param {string} key - Translation key
 * @param {string} fallback - Fallback text
 * @param {Object} replacements - { placeholder: value }
 * @returns {string}
 */
export function trans(key, fallback, replacements = {}) {
    let text = getTranslation(key, fallback);
    for (const [k, v] of Object.entries(replacements)) {
        text = text.replace(`:${k}`, v);
    }
    return text;
}

/**
 * Creates a debounced wrapper. Timer is scoped to the returned closure.
 * @param {Function} fn
 * @param {number} ms
 * @returns {Function}
 */
export function debounce(fn, ms) {
    let timer = null;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), ms);
    };
}

/**
 * Validate a URL to prevent javascript: protocol injection.
 * Only allows relative paths and http(s) protocols.
 * @param {string} url
 * @returns {boolean}
 */
export function isSafeUrl(url) {
    if (!url) return false;
    const str = String(url).trim();
    return str.startsWith('/') || str.startsWith('http://') || str.startsWith('https://');
}

/**
 * Validate and sanitize a URL for use in HTML attributes.
 * Only allows http: and https: protocols to prevent javascript: XSS.
 * @param {string} url
 * @returns {string}
 */
export function sanitizeUrl(url) {
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
 * DOM helper: create an element with class, optional text or children.
 * @param {string} tag
 * @param {string} [className]
 * @param {string|Array<HTMLElement>} [textOrChildren]
 * @returns {HTMLElement}
 */
export function el(tag, className, textOrChildren) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (typeof textOrChildren === 'string') {
        node.textContent = textOrChildren;
    } else if (Array.isArray(textOrChildren)) {
        textOrChildren.forEach((child) => {
            if (child) node.appendChild(child);
        });
    }
    return node;
}

/**
 * Create a focus trap that cycles Tab/Shift+Tab within a container.
 * Re-queries focusable elements on each Tab press (correct for dynamic content).
 * Filters out invisible elements via offsetParent check.
 * @param {HTMLElement} container
 * @returns {{ activate: Function, deactivate: Function }}
 */
export function createFocusTrap(container) {
    let handler = null;

    function activate() {
        deactivate();
        handler = (e) => {
            if (e.key !== 'Tab') return;

            const focusable = Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR))
                .filter((el) => el.offsetParent !== null);
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
        container.addEventListener('keydown', handler);
    }

    function deactivate() {
        if (handler) {
            container.removeEventListener('keydown', handler);
            handler = null;
        }
    }

    return { activate, deactivate };
}

/**
 * Create a spinner element using Bootstrap Icons.
 * Replaces the 30-line manual SVG spinner with a lightweight icon.
 * @param {string} [label] - Accessible label text
 * @returns {HTMLElement}
 */
export function createSpinner(label) {
    const span = document.createElement('span');
    span.className = 'inline-flex items-center gap-2';
    const icon = document.createElement('i');
    icon.className = 'bi bi-arrow-repeat animate-spin';
    span.appendChild(icon);
    if (label) {
        span.appendChild(document.createTextNode(' ' + label));
    }
    return span;
}
