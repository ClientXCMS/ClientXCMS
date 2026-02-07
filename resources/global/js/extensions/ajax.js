import Swal from 'sweetalert2';
import { getTranslation } from './utils.js';

// Re-export for backward compatibility with modules that imported from ajax.js
export { getTranslation };

/**
 * Returns the CSRF token from the page meta tag.
 * @returns {string|null}
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : null;
}

/**
 * Returns a user-friendly error message for common HTTP status codes.
 * Uses translation keys from the global translations object when available.
 * @param {number} status - HTTP status code
 * @returns {string}
 */
function getDefaultMessage(status) {
    switch (status) {
        case 403:
            return getTranslation('error_permission', 'You do not have permission to perform this action.');
        case 422:
            return getTranslation('error_validation', 'Validation error. Please check your input.');
        case 500:
            return getTranslation('error_server', 'An internal server error occurred. Please try again later.');
        default:
            return getTranslation('error_unknown', 'An unexpected error occurred.');
    }
}

/**
 * Displays an error notification using SweetAlert2.
 * @param {string} message - The error message to display
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
    });
}

/**
 * Performs an HTTP request with CSRF token, JSON headers, and uniform error handling.
 * Follows ADR-03 JSON contract: { success, message, data, errors }.
 *
 * @param {string} url - The request URL
 * @param {RequestInit} [options={}] - Fetch options
 * @returns {Promise<Object>} Parsed JSON response on success
 * @throws {Error} On network or HTTP errors
 */
export async function request(url, options = {}) {
    const { silent = false, ...fetchOptions } = options;
    const csrfToken = getCsrfToken();

    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...fetchOptions.headers,
    };

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    let response;
    try {
        response = await fetch(url, { ...fetchOptions, headers });
    } catch (err) {
        if (!silent) {
            showError(getTranslation('error_network', 'Network error. Please check your connection and try again.'));
        }
        document.dispatchEvent(new CustomEvent('extension:error', {
            detail: { type: 'network', error: err },
        }));
        throw err;
    }

    if (!response.ok) {
        let json = {};
        try {
            json = await response.json();
        } catch {
            /* ignore JSON parse failures */
        }

        const message = json.message || getDefaultMessage(response.status);
        if (!silent) {
            showError(message);
        }

        document.dispatchEvent(new CustomEvent('extension:error', {
            detail: { type: 'http', status: response.status, data: json },
        }));

        const error = new Error(message);
        error.status = response.status;
        error.data = json;
        throw error;
    }

    return response.json();
}

/**
 * Performs a POST request with JSON body.
 *
 * @param {string} url - The request URL
 * @param {Object} [data={}] - Request body data
 * @returns {Promise<Object>} Parsed JSON response
 */
export async function post(url, data = {}) {
    return request(url, {
        method: 'POST',
        body: JSON.stringify(data),
    });
}

/**
 * Performs a GET request.
 *
 * @param {string} url - The request URL
 * @returns {Promise<Object>} Parsed JSON response
 */
export async function get(url) {
    return request(url, { method: 'GET' });
}
