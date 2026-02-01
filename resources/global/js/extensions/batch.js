/**
 * Batch Engine for Extension Operations
 * Stories 3.2-3.6: Batch install, progress bar, recap, update, bulk selection.
 *
 * ADR-02: Client-driven sequential requests. JS sends requests ONE BY ONE.
 * ADR-05: Cache clear via POST /extensions/clear-cache ONLY at end of batch.
 * PA-3: window.location.reload() ONLY after batch completion + cache clear.
 *
 * Security: All dynamic content is escaped via escapeHtml() from utils.js
 * before insertion into innerHTML. This prevents XSS from extension names,
 * UUIDs, and other user-influenced data.
 */

import { escapeHtml } from './utils.js';
import { request, getTranslation } from './ajax.js';

/** Max wait time (ms) for user decision on batch error before auto-skip (M4). */
const ERROR_PROMPT_TIMEOUT_MS = 60000;

/**
 * Replace :placeholder tokens in a translation string.
 * @param {string} key - Translation key
 * @param {string} fallback - Fallback text
 * @param {Object} replacements - { placeholder: value }
 * @returns {string}
 */
function trans(key, fallback, replacements = {}) {
    let text = getTranslation(key, fallback);
    for (const [k, v] of Object.entries(replacements)) {
        text = text.replace(`:${k}`, v);
    }
    return text;
}

/**
 * Make a silent POST request for batch operations (L1 fix).
 * Uses silent mode to suppress SweetAlert toasts (batch has its own error UI).
 * @param {string} url
 * @param {Object} [data] - JSON body (omit for bodyless POST)
 * @returns {Promise<Object>}
 */
async function batchPost(url, data) {
    const opts = { method: 'POST', silent: true };
    if (data) {
        opts.body = JSON.stringify(data);
    }
    return request(url, opts);
}

/**
 * Build the route URL for an extension action (enable/disable/update).
 * Pattern: base/{type}/{uuid}/{action}
 * @param {string} type - modules/addons/themes
 * @param {string} uuid
 * @param {string} action - update/enable/disable
 * @returns {string}
 */
function buildUrl(type, uuid, action) {
    const base = window.__extensionsRoutes?.base || '/admin/settings/extensions';
    return `${base}/${type}/${uuid}/${action}`;
}

/**
 * Build the route URL for the install endpoint (H1 fix).
 * Pattern: base/install/{type}/{uuid}
 * @param {string} type
 * @param {string} uuid
 * @returns {string}
 */
function buildInstallUrl(type, uuid) {
    const base = window.__extensionsRoutes?.base || '/admin/settings/extensions';
    return `${base}/install/${type}/${uuid}`;
}

/**
 * Build the clear cache URL.
 * @returns {string}
 */
function clearCacheUrl() {
    const base = window.__extensionsRoutes?.base || '/admin/settings/extensions';
    return `${base}/clear-cache`;
}

/**
 * BatchEngine class
 * Handles batch install, update, progress tracking, error recovery,
 * recap display, update banner, and bulk selection.
 */
export class BatchEngine {
    /**
     * @param {import('./cart.js').CartManager} cart
     */
    constructor(cart) {
        this.cart = cart;
        this.isRunning = false;
        this.isStopped = false;
        this.results = [];
        this.currentIndex = 0;
        this.total = 0;
        this.mode = 'install-activate';
        this.bulkModeActive = false;
        this._bindEvents();
    }

    // --- Story 3.2: Batch Install ---

    /**
     * Start a batch operation.
     * H1 fix: install modes use the dedicated install route with activate flag.
     * @param {'install-activate'|'install-only'|'update'} mode
     */
    async start(mode) {
        if (this.isRunning) return;

        const items = this.cart.getItems();
        if (items.length === 0) return;

        this.isRunning = true;
        this.isStopped = false;
        this.mode = mode;
        this.results = [];
        this.currentIndex = 0;
        this.total = items.length;

        this.cart.close();
        this._showProgress();
        this._resetProgressItems(items);

        // ADR-02: Strictly sequential (one at a time)
        for (let i = 0; i < items.length; i++) {
            if (this.isStopped) break;

            this.currentIndex = i;
            const item = items[i];
            const phase = mode === 'update' ? 'updating' : 'installing';

            this._updateProgress(item, i, items.length, phase);
            this._setProgressItemState(item.uuid, 'active');

            try {
                if (mode === 'update') {
                    await batchPost(buildUrl(item.type, item.uuid, 'update'));
                } else {
                    // H1: Use install route with activate parameter (H3: controller support)
                    await batchPost(buildInstallUrl(item.type, item.uuid), {
                        activate: mode === 'install-activate',
                    });
                }

                this.results.push({ ...item, status: 'success' });
                this._setProgressItemState(item.uuid, 'success');
            } catch (error) {
                this.results.push({ ...item, status: 'error', error: error.message });
                this._setProgressItemState(item.uuid, 'error');

                const decision = await this._promptError(item, error.message);

                if (decision === 'retry') {
                    i--;
                    this.results.pop();
                    continue;
                } else if (decision === 'skip') {
                    continue;
                } else {
                    this.isStopped = true;
                    break;
                }
            }
        }

        this.isRunning = false;
        this._showRecap();
    }

    /**
     * Stop the current batch operation.
     */
    stop() {
        this.isStopped = true;

        const stopBtn = document.querySelector('[data-action="batch-stop"]');
        if (stopBtn) {
            stopBtn.disabled = true;
            stopBtn.textContent = getTranslation('batch_stopping', 'Stopping...');
        }

        const text = document.getElementById('batch-progress-text');
        if (text) {
            text.textContent = getTranslation('batch_stopping', 'Stopping...');
        }
    }

    // --- Story 3.3: Progress Bar ---

    /**
     * Show the progress bar container.
     */
    _showProgress() {
        const progress = document.getElementById('batch-progress');
        const recap = document.getElementById('batch-recap');
        if (progress) {
            progress.classList.remove('hidden');
            progress.setAttribute('aria-hidden', 'false');
        }
        if (recap) {
            recap.classList.add('hidden');
            recap.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Hide the progress bar container.
     */
    _hideProgress() {
        const progress = document.getElementById('batch-progress');
        if (progress) {
            progress.classList.add('hidden');
            progress.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Reset progress items list using safe DOM construction.
     * @param {Array} items
     */
    _resetProgressItems(items) {
        const list = document.getElementById('batch-progress-items');
        if (!list) return;

        list.textContent = '';
        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 text-sm py-1';
            row.setAttribute('data-progress-item', item.uuid);

            const iconSpan = document.createElement('span');
            iconSpan.className = 'progress-icon text-gray-400 dark:text-slate-500';
            const icon = document.createElement('i');
            icon.className = 'bi bi-circle';
            iconSpan.appendChild(icon);

            const nameSpan = document.createElement('span');
            nameSpan.className = 'text-gray-700 dark:text-gray-300';
            nameSpan.textContent = item.name;

            row.appendChild(iconSpan);
            row.appendChild(nameSpan);
            list.appendChild(row);
        });
    }

    /**
     * Update the progress bar percentage and text.
     * L2 fix: uses translation keys instead of hardcoded French.
     * @param {Object} item
     * @param {number} current - 0-indexed
     * @param {number} total
     * @param {'installing'|'activating'|'updating'} phase
     */
    _updateProgress(item, current, total, phase = 'installing') {
        const bar = document.getElementById('batch-progress-bar');
        const text = document.getElementById('batch-progress-text');
        const pctEl = document.getElementById('batch-progress-percentage');

        const MIDPOINT_OFFSET = 0.5;
        const pct = total > 0 ? Math.round(((current + MIDPOINT_OFFSET) / total) * 100) : 0;

        if (bar) {
            bar.style.width = `${pct}%`;
            bar.setAttribute('aria-valuenow', pct);
        }

        const phaseLabels = {
            installing: getTranslation('batch_installing', 'Installation'),
            activating: getTranslation('batch_activating', 'Activation'),
            updating: getTranslation('batch_updating', 'Mise a jour'),
            deactivating: getTranslation('batch_deactivating', 'D\u00e9sactivation'),
        };

        if (text) {
            text.textContent = trans('batch_progress_text', ':phase de :name (:current/:total)', {
                phase: phaseLabels[phase] || phase,
                name: item.name,
                current: current + 1,
                total,
            });
        }
        if (pctEl) {
            pctEl.textContent = `${pct}%`;
        }
    }

    /**
     * Set the visual state of a progress list item.
     * @param {string} uuid
     * @param {'active'|'success'|'error'} state
     */
    _setProgressItemState(uuid, state) {
        const el = document.querySelector(`[data-progress-item="${CSS.escape(uuid)}"]`);
        if (!el) return;

        const iconEl = el.querySelector('.progress-icon');
        if (!iconEl) return;

        const icon = document.createElement('i');
        iconEl.textContent = '';

        if (state === 'active') {
            icon.className = 'bi bi-arrow-repeat animate-spin text-indigo-500';
        } else if (state === 'success') {
            icon.className = 'bi bi-check-circle-fill text-green-500';
        } else if (state === 'error') {
            icon.className = 'bi bi-x-circle-fill text-red-500';
        }

        iconEl.appendChild(icon);
    }

    /**
     * Show error UI and wait for user decision (Retry/Skip/Stop).
     * FR27: Already processed extensions preserved on error.
     * M4 fix: includes timeout fallback to auto-skip after ERROR_PROMPT_TIMEOUT_MS.
     * @param {Object} item
     * @param {string} errorMessage
     * @returns {Promise<'retry'|'skip'|'stop'>}
     */
    _promptError(item, errorMessage) {
        return new Promise((resolve) => {
            let resolved = false;

            const cleanup = (action) => {
                if (resolved) return;
                resolved = true;
                clearTimeout(timeoutId);
                if (errorEl) errorEl.classList.add('hidden');
                controller.abort();
                resolve(action);
            };

            const errorEl = document.getElementById('batch-error');
            const errorName = document.getElementById('batch-error-name');
            const errorMsg = document.getElementById('batch-error-message');

            // M4: If error UI element is missing, auto-skip to prevent infinite hang
            if (!errorEl) {
                resolve('skip');
                return;
            }

            errorEl.classList.remove('hidden');
            if (errorName) errorName.textContent = item.name;
            if (errorMsg) {
                errorMsg.textContent = errorMessage
                    || getTranslation('batch_error_default', 'An error occurred during processing.');
            }

            // M4: Timeout fallback to auto-skip
            const timeoutId = setTimeout(() => cleanup('skip'), ERROR_PROMPT_TIMEOUT_MS);

            const controller = new AbortController();
            const decisionHandler = (e) => {
                const btn = e.target.closest('[data-batch-action]');
                if (!btn) return;

                const action = btn.dataset.batchAction;
                if (['retry', 'skip', 'stop'].includes(action)) {
                    cleanup(action);
                }
            };

            document.addEventListener('click', decisionHandler, { signal: controller.signal });
        });
    }

    // --- Story 3.4: Recap ---

    /**
     * Determine recap header configuration based on batch outcome.
     * M2 fix: extracted from _showRecap to keep methods under 50 lines.
     * @param {number} successCount
     * @param {number} failCount
     * @param {number} total
     * @returns {{ bgClass: string, textClass: string, icon: string, message: string }}
     */
    _getRecapHeaderConfig(successCount, failCount, total) {
        if (failCount === 0 && total > 0) {
            return {
                bgClass: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                textClass: 'text-green-700 dark:text-green-400',
                icon: 'bi-check-circle-fill',
                message: trans('batch_success_all', ':success/:total installed successfully', {
                    success: successCount, total,
                }),
            };
        }
        if (successCount === 0) {
            return {
                bgClass: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                textClass: 'text-red-700 dark:text-red-400',
                icon: 'bi-x-circle-fill',
                message: trans('batch_failure_all', 'Installation failed (:count extension(s))', {
                    count: total,
                }),
            };
        }
        return {
            bgClass: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
            textClass: 'text-amber-700 dark:text-amber-400',
            icon: 'bi-exclamation-triangle-fill',
            message: trans('batch_partial', ':success/:total succeeded, :failed failed', {
                success: successCount, total, failed: failCount,
            }),
        };
    }

    /**
     * Render a single recap detail row using safe DOM construction.
     * M2 fix: extracted from _showRecap to keep methods under 50 lines.
     * @param {{ name: string, uuid: string, type: string, status: string }} result
     * @returns {HTMLElement}
     */
    _createRecapDetailRow(result) {
        const row = document.createElement('div');
        row.className = `flex items-center justify-between p-2.5 rounded-lg ${
            result.status === 'success' ? 'bg-green-50 dark:bg-green-900/10' : 'bg-red-50 dark:bg-red-900/10'
        }`;

        const left = document.createElement('div');
        left.className = 'flex items-center gap-2 min-w-0';

        const icon = document.createElement('i');
        icon.className = `bi flex-shrink-0 ${
            result.status === 'success' ? 'bi-check-circle-fill text-green-500' : 'bi-x-circle-fill text-red-500'
        }`;
        left.appendChild(icon);

        const name = document.createElement('span');
        name.className = 'text-sm text-gray-900 dark:text-white truncate';
        name.textContent = result.name;
        left.appendChild(name);

        row.appendChild(left);

        if (result.status === 'error') {
            const retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'flex-shrink-0 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline px-2 py-1';
            retryBtn.setAttribute('data-action', 'retry-single');
            retryBtn.setAttribute('data-uuid', result.uuid);
            retryBtn.setAttribute('data-type', result.type);
            retryBtn.textContent = getTranslation('batch_retry', 'Retry');
            row.appendChild(retryBtn);
        }

        return row;
    }

    /**
     * Show the batch recap, replacing the progress bar.
     */
    _showRecap() {
        this._hideProgress();

        const recap = document.getElementById('batch-recap');
        if (!recap) return;

        recap.classList.remove('hidden');
        recap.setAttribute('aria-hidden', 'false');

        const successCount = this.results.filter(r => r.status === 'success').length;
        const failCount = this.results.filter(r => r.status === 'error').length;
        const total = this.results.length;

        const header = document.getElementById('recap-header');
        if (header) {
            const config = this._getRecapHeaderConfig(successCount, failCount, total);
            header.className = `p-4 border-b ${config.bgClass}`;
            header.textContent = '';

            const wrapper = document.createElement('div');
            wrapper.className = `flex items-center gap-2 ${config.textClass}`;

            const icon = document.createElement('i');
            icon.className = `bi ${config.icon} text-lg`;
            wrapper.appendChild(icon);

            const msg = document.createElement('span');
            msg.className = 'font-semibold';
            msg.textContent = config.message;
            wrapper.appendChild(msg);

            header.appendChild(wrapper);
        }

        const details = document.getElementById('recap-details');
        if (details) {
            details.textContent = '';
            this.results.forEach(r => details.appendChild(this._createRecapDetailRow(r)));
        }

        const closeBtn = document.getElementById('recap-close-btn');
        if (closeBtn) {
            closeBtn.disabled = false;
        }

        // L3 fix: event name uses dash separator per ADR-06 convention
        document.dispatchEvent(new CustomEvent('extension:batch-complete', {
            detail: { results: this.results },
        }));
    }

    /**
     * Close recap: POST clear-cache then reload (ADR-05, PA-3).
     * This is the ONLY authorized window.location.reload().
     */
    async closeRecap() {
        const closeBtn = document.getElementById('recap-close-btn');
        if (closeBtn) {
            closeBtn.disabled = true;
            closeBtn.textContent = '';

            const spinner = document.createElement('i');
            spinner.className = 'bi bi-arrow-repeat animate-spin mr-1';
            closeBtn.appendChild(spinner);
            closeBtn.appendChild(document.createTextNode(
                getTranslation('batch_clearing_cache', 'Clearing cache...')
            ));
        }

        try {
            await batchPost(clearCacheUrl());
        } catch {
            // Cache clear failed, reload anyway
        }

        this.cart.clear();
        this.results = [];
        this.isRunning = false;
        this.isStopped = false;

        // PA-3: This is the ONLY authorized reload
        window.location.reload();
    }

    /**
     * Retry a single failed extension from the recap.
     * H1 fix: uses correct route based on batch mode.
     * @param {string} uuid
     */
    async retrySingle(uuid) {
        const ext = window.__extensionsData?.[uuid];
        if (!ext) {
            const orphanBtn = document.querySelector(
                `[data-action="retry-single"][data-uuid="${CSS.escape(uuid)}"]`
            );
            if (orphanBtn) {
                orphanBtn.textContent = '';
                const icon = document.createElement('i');
                icon.className = 'bi bi-exclamation-triangle text-amber-500';
                orphanBtn.appendChild(icon);
                orphanBtn.disabled = true;
            }
            return;
        }

        this.results = this.results.filter(r => r.uuid !== uuid);

        const retryBtn = document.querySelector(
            `[data-action="retry-single"][data-uuid="${CSS.escape(uuid)}"]`
        );
        if (retryBtn) {
            retryBtn.textContent = '';
            const spinner = document.createElement('i');
            spinner.className = 'bi bi-arrow-repeat animate-spin';
            retryBtn.appendChild(spinner);
            retryBtn.disabled = true;
        }

        try {
            if (this.mode === 'update') {
                await batchPost(buildUrl(ext.type, ext.uuid, 'update'));
            } else {
                await batchPost(buildInstallUrl(ext.type, ext.uuid), {
                    activate: this.mode === 'install-activate',
                });
            }
            this.results.push({ ...ext, status: 'success' });
        } catch (error) {
            this.results.push({ ...ext, status: 'error', error: error.message });
        }

        this._showRecap();
    }

    // --- Story 3.5: Update Banner ---

    /**
     * Initialize the update banner based on available updates.
     */
    initUpdateBanner() {
        const extensionsData = window.__extensionsData;
        if (!extensionsData) return;

        const updatable = Object.values(extensionsData).filter(e => e.hasUpdate);
        const banner = document.getElementById('update-banner');
        const countEl = document.getElementById('update-count');

        if (banner && updatable.length > 0) {
            banner.classList.remove('hidden');
            if (countEl) {
                countEl.textContent = trans('batch_updates_available', ':count update(s) available', {
                    count: updatable.length,
                });
            }
        }
    }

    /**
     * Add all update-available extensions to cart and open drawer.
     */
    addUpdatesToCart() {
        const extensionsData = window.__extensionsData || {};
        Object.values(extensionsData).forEach(ext => {
            if (ext.hasUpdate) {
                this.cart.add(ext);
            }
        });
        this.cart.open();
    }

    // --- Story 3.6: Bulk Selection ---

    /**
     * Toggle bulk selection mode on/off.
     */
    toggleBulkMode() {
        this.bulkModeActive = !this.bulkModeActive;
        const bar = document.getElementById('bulk-action-bar');
        const toggleBtn = document.querySelector('[data-action="toggle-bulk-mode"]');

        if (this.bulkModeActive) {
            document.body.classList.add('bulk-mode-active');
            this._injectBulkCheckboxes();

            if (bar) bar.classList.remove('hidden');
            if (toggleBtn) {
                toggleBtn.textContent = '';
                const icon = document.createElement('i');
                icon.className = 'bi bi-x-lg mr-1';
                toggleBtn.appendChild(icon);
                toggleBtn.appendChild(document.createTextNode(
                    getTranslation('batch_cancel_selection', 'Cancel selection')
                ));
            }
        } else {
            this.exitBulkMode();
        }
    }

    /**
     * Inject checkboxes on installed extension cards for bulk selection.
     */
    _injectBulkCheckboxes() {
        document.querySelectorAll('.extension-item').forEach(card => {
            const uuid = card.dataset.uuid;
            const ext = window.__extensionsData?.[uuid];
            if (ext && ext.isInstalled) {
                const selectLabel = getTranslation('batch_select', 'Select');
                const wrapper = document.createElement('div');
                wrapper.className = 'bulk-checkbox-wrapper absolute top-2.5 left-2.5 z-10';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'bulk-checkbox w-5 h-5 rounded border-2 border-gray-300 dark:border-slate-500 text-indigo-600 focus:ring-indigo-500 cursor-pointer shadow-sm';
                checkbox.setAttribute('data-uuid', ext.uuid);
                checkbox.setAttribute('aria-label', `${selectLabel} ${ext.name}`);

                wrapper.appendChild(checkbox);
                card.style.position = 'relative';
                card.prepend(wrapper);
            }
        });
    }

    /**
     * Exit bulk selection mode, remove checkboxes.
     */
    exitBulkMode() {
        this.bulkModeActive = false;
        document.body.classList.remove('bulk-mode-active');
        document.querySelectorAll('.bulk-checkbox-wrapper').forEach(el => el.remove());
        document.querySelectorAll('.extension-item.bulk-selected').forEach(el => el.classList.remove('bulk-selected'));

        const bar = document.getElementById('bulk-action-bar');
        if (bar) bar.classList.add('hidden');

        const toggleBtn = document.querySelector('[data-action="toggle-bulk-mode"]');
        if (toggleBtn) {
            toggleBtn.textContent = '';
            const icon = document.createElement('i');
            icon.className = 'bi bi-check2-square mr-1';
            toggleBtn.appendChild(icon);
            toggleBtn.appendChild(document.createTextNode(
                getTranslation('batch_select', 'Select')
            ));
        }

        this._updateBulkCount();
    }

    /**
     * Update the bulk selection count display.
     */
    _updateBulkCount() {
        const checked = document.querySelectorAll('.bulk-checkbox:checked');
        const countEl = document.getElementById('bulk-count');
        if (countEl) {
            countEl.textContent = trans('batch_selected_count', ':count selected', {
                count: checked.length,
            });
        }

        const activateBtn = document.querySelector('[data-action="bulk-activate"]');
        const deactivateBtn = document.querySelector('[data-action="bulk-deactivate"]');
        if (activateBtn) activateBtn.disabled = checked.length === 0;
        if (deactivateBtn) deactivateBtn.disabled = checked.length === 0;
    }

    /**
     * Get the list of extensions selected via bulk checkboxes.
     * M2 fix: extracted from bulkAction to reduce method size.
     * @returns {Array<Object>}
     */
    _getSelectedExtensions() {
        const checked = document.querySelectorAll('.bulk-checkbox:checked');
        const extensions = [];
        checked.forEach(cb => {
            const uuid = cb.dataset.uuid;
            const ext = window.__extensionsData?.[uuid];
            if (ext) extensions.push(ext);
        });
        return extensions;
    }

    /**
     * Show SweetAlert2 confirmation for bulk action.
     * M2 fix: extracted from bulkAction to reduce method size.
     * @param {Array<Object>} extensions
     * @param {'enable'|'disable'} action
     * @returns {Promise<boolean>}
     */
    async _confirmBulkAction(extensions, action) {
        if (typeof Swal === 'undefined') return true;

        const actionLabel = action === 'enable'
            ? getTranslation('enable', 'Enable')
            : getTranslation('disable', 'Disable');

        // Build safe list using DOM, then serialize for Swal html
        const ul = document.createElement('ul');
        ul.className = 'text-left text-sm mt-2 space-y-1';
        extensions.forEach(e => {
            const li = document.createElement('li');
            li.className = 'flex items-center gap-2';
            const icon = document.createElement('i');
            icon.className = 'bi bi-puzzle text-gray-400';
            li.appendChild(icon);
            li.appendChild(document.createTextNode(` ${e.name}`));
            ul.appendChild(li);
        });

        const result = await Swal.fire({
            title: `${actionLabel} ${extensions.length} extension${extensions.length > 1 ? 's' : ''} ?`,
            html: ul,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: actionLabel,
            cancelButtonText: getTranslation('cancel', 'Cancel'),
            confirmButtonColor: action === 'enable' ? '#10b981' : '#ef4444',
        });

        return result.isConfirmed;
    }

    /**
     * Perform a bulk action (enable/disable) on selected extensions.
     * @param {'enable'|'disable'} action
     */
    async bulkAction(action) {
        const extensions = this._getSelectedExtensions();
        if (extensions.length === 0) return;

        const confirmed = await this._confirmBulkAction(extensions, action);
        if (!confirmed) return;

        this.isRunning = true;
        this.isStopped = false;
        this.results = [];
        this.total = extensions.length;
        this.mode = action;

        this._showProgress();
        this._resetProgressItems(extensions);

        for (let i = 0; i < extensions.length; i++) {
            if (this.isStopped) break;

            const ext = extensions[i];
            const phase = action === 'enable' ? 'activating' : 'deactivating';
            this._updateProgress(ext, i, extensions.length, phase);
            this._setProgressItemState(ext.uuid, 'active');

            try {
                await batchPost(buildUrl(ext.type, ext.uuid, action));
                this.results.push({ ...ext, status: 'success' });
                this._setProgressItemState(ext.uuid, 'success');
            } catch (error) {
                this.results.push({ ...ext, status: 'error', error: error.message });
                this._setProgressItemState(ext.uuid, 'error');

                const decision = await this._promptError(ext, error.message);
                if (decision === 'retry') {
                    i--;
                    this.results.pop();
                    continue;
                } else if (decision === 'skip') {
                    continue;
                } else {
                    this.isStopped = true;
                    break;
                }
            }
        }

        this.isRunning = false;
        this.exitBulkMode();
        this._showRecap();
    }

    // --- Event Bindings ---

    /**
     * Bind all event listeners using event delegation.
     */
    _bindEvents() {
        document.addEventListener('click', (e) => {
            // Story 3.2: Install and activate all
            if (e.target.closest('[data-action="batch-install-activate"]')) {
                e.preventDefault();
                this.start('install-activate');
                return;
            }

            // Story 3.2: Install only
            if (e.target.closest('[data-action="batch-install-only"]')) {
                e.preventDefault();
                this.start('install-only');
                return;
            }

            // Story 3.5: Update selection
            if (e.target.closest('[data-action="batch-update"]')) {
                e.preventDefault();
                this.start('update');
                return;
            }

            // Story 3.3: Stop batch
            if (e.target.closest('[data-action="batch-stop"]')) {
                e.preventDefault();
                this.stop();
                return;
            }

            // Story 3.4: Close recap (cache clear + reload)
            if (e.target.closest('[data-action="close-recap"]')) {
                e.preventDefault();
                this.closeRecap();
                return;
            }

            // Story 3.4: Retry single from recap
            const retryBtn = e.target.closest('[data-action="retry-single"]');
            if (retryBtn) {
                e.preventDefault();
                this.retrySingle(retryBtn.dataset.uuid);
                return;
            }

            // Story 3.5: Add all updates to cart
            if (e.target.closest('[data-action="add-updates-to-cart"]')) {
                e.preventDefault();
                this.addUpdatesToCart();
                return;
            }

            // Story 3.6: Toggle bulk mode
            if (e.target.closest('[data-action="toggle-bulk-mode"]')) {
                e.preventDefault();
                this.toggleBulkMode();
                return;
            }

            // Story 3.6: Bulk activate
            if (e.target.closest('[data-action="bulk-activate"]')) {
                e.preventDefault();
                this.bulkAction('enable');
                return;
            }

            // Story 3.6: Bulk deactivate
            if (e.target.closest('[data-action="bulk-deactivate"]')) {
                e.preventDefault();
                this.bulkAction('disable');
                return;
            }

            // Story 3.6: Bulk cancel
            if (e.target.closest('[data-action="bulk-cancel"]')) {
                e.preventDefault();
                this.exitBulkMode();
                return;
            }
        });

        // Story 3.6: Update bulk count when checkboxes change
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('bulk-checkbox')) {
                this._updateBulkCount();
                // Fallback for browsers without CSS :has() support
                const card = e.target.closest('.extension-item');
                if (card) {
                    card.classList.toggle('bulk-selected', e.target.checked);
                }
            }
        });
    }
}
