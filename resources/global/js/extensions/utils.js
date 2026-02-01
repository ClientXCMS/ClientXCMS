/**
 * Shared utilities for the extensions module.
 * Extracted to avoid duplication across batch.js and cart.js.
 */

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
