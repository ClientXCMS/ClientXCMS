/**
 * Extensions AJAX Action Handlers (Stories 1.5 & 1.6)
 *
 * Wires click handlers on enable/disable/install/update buttons
 * to call backend AJAX endpoints without page reload.
 *
 * Uses SweetAlert2 for confirmations and error display.
 * Emits extension:* CustomEvents for cross-module communication.
 */
import Swal from 'sweetalert2';
import { getTranslation, createSpinner } from './utils.js';

// --- Utility Functions ---

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
}

function showSpinner(button) {
    button._originalNodes = Array.from(button.childNodes).map((n) => n.cloneNode(true));
    button._originalDisabled = button.disabled;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = '';
    button.appendChild(createSpinner(getTranslation('processing', 'Processing...')));
}

function hideSpinner(button) {
    button.disabled = button._originalDisabled || false;
    button.removeAttribute('aria-busy');
    if (button._originalNodes) {
        button.textContent = '';
        button._originalNodes.forEach((n) => { button.appendChild(n.cloneNode(true)); });
    }
}

function announceResult(message) {
    const region = document.getElementById('js-extension-announcer');
    if (region) {
        region.textContent = message;
    }
}

function emitEvent(name, detail) {
    document.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
}

// --- AJAX ---

function ajaxPost(url) {
    const token = getCsrfToken();
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body: new URLSearchParams({ _token: token }),
    }).then((response) => {
        return response.json().catch(() => ({})).then((json) => {
            if (!response.ok) {
                const error = new Error(json.message || 'Request failed');
                error.status = response.status;
                error.data = json.data || {};
                error.errors = json.errors || [];
                throw error;
            }
            return json;
        });
    });
}

// --- Safe DOM Button Builder ---

function createActionForm(actionUrl, buttonClass, iconClass, labelKey, labelFallback) {
    const token = getCsrfToken();
    const form = document.createElement('form');
    form.action = actionUrl;
    form.method = 'POST';

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = token;
    form.appendChild(tokenInput);

    const btn = document.createElement('button');
    btn.type = 'submit';
    btn.className = buttonClass + ' w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0';

    const icon = document.createElement('i');
    icon.className = iconClass;
    btn.appendChild(icon);
    btn.appendChild(document.createTextNode(getTranslation(labelKey, labelFallback)));

    form.appendChild(btn);
    return form;
}

// --- Card DOM Update ---

function updateCardStatus(card, newStatus) {
    if (!card) return;

    card.setAttribute('data-status', newStatus);
    if (newStatus !== 'updated') {
        card.setAttribute('data-enabled', newStatus === 'enabled' ? 'true' : 'false');
    }
    if (newStatus !== 'not_installed') {
        card.setAttribute('data-installed', 'true');
    }
    if (newStatus === 'updated') {
        card.setAttribute('data-has-update', 'false');
    }

    const badge = card.querySelector('.js-status-badge');
    if (!badge) return;

    badge.classList.remove(
        'bg-green-500', 'bg-blue-500', 'bg-amber-500',
        'dark:bg-green-600', 'dark:bg-blue-600', 'dark:bg-amber-600'
    );

    const icon = badge.querySelector('i');
    const text = badge.querySelector('.js-status-text');

    switch (newStatus) {
        case 'enabled':
            badge.classList.add('bg-green-500');
            badge.classList.remove('hidden');
            if (icon) { icon.className = 'bi bi-check-circle-fill mr-1'; }
            if (text) { text.textContent = getTranslation('enabled', 'Enabled'); }
            break;
        case 'disabled':
        case 'installed':
            badge.classList.add('bg-blue-500');
            badge.classList.remove('hidden');
            if (icon) { icon.className = 'bi bi-check mr-1'; }
            if (text) { text.textContent = getTranslation('installed', 'Installed'); }
            break;
        case 'updated':
            if (card.dataset.enabled === 'true') {
                badge.classList.add('bg-green-500');
                badge.classList.remove('hidden');
                if (icon) { icon.className = 'bi bi-check-circle-fill mr-1'; }
                if (text) { text.textContent = getTranslation('enabled', 'Enabled'); }
            } else {
                badge.classList.add('bg-blue-500');
                badge.classList.remove('hidden');
                if (icon) { icon.className = 'bi bi-check mr-1'; }
                if (text) { text.textContent = getTranslation('installed', 'Installed'); }
            }
            {
                const updateBadge = card.querySelector('.js-update-badge');
                if (updateBadge) updateBadge.remove();
            }
            break;
        case 'not_installed':
            card.setAttribute('data-installed', 'false');
            card.setAttribute('data-enabled', 'false');
            badge.classList.add('hidden');
            break;
    }
}

function replaceActionButtons(card, newStatus) {
    const actionsContainer = card.querySelector('.js-card-actions');
    if (!actionsContainer) return;

    const enableBtn = actionsContainer.querySelector('.js-btn-enable');
    const disableBtn = actionsContainer.querySelector('.js-btn-disable');
    const installBtn = actionsContainer.querySelector('.js-btn-install');
    const uninstallBtn = actionsContainer.querySelector('.js-btn-uninstall');

    if (enableBtn) {
        const ef = enableBtn.closest('form');
        if (ef) ef.remove(); else enableBtn.remove();
    }
    if (disableBtn) {
        const df = disableBtn.closest('form');
        if (df) df.remove(); else disableBtn.remove();
    }
    if (installBtn) {
        const inf = installBtn.closest('form');
        if (inf) inf.remove(); else installBtn.remove();
    }
    if (uninstallBtn) {
        const uf = uninstallBtn.closest('form');
        if (uf) uf.remove(); else uninstallBtn.remove();
    }

    const detailsBtn = actionsContainer.querySelector('.js-btn-details');
    let form;

    if (newStatus === 'enabled') {
        const disableUrl = card.dataset.disableUrl;
        if (disableUrl) {
            form = createActionForm(
                disableUrl,
                'js-btn-disable bg-red-500 hover:bg-red-600 text-white',
                'bi bi-x-circle',
                'disable', 'Disable'
            );
            if (detailsBtn) {
                actionsContainer.insertBefore(form, detailsBtn);
            } else {
                actionsContainer.appendChild(form);
            }
            form.querySelector('.js-btn-disable').addEventListener('click', handleDisable);
        }
    } else if (newStatus === 'disabled' || newStatus === 'installed') {
        const enableUrl = card.dataset.enableUrl;
        if (enableUrl && card.dataset.activable === 'true') {
            form = createActionForm(
                enableUrl,
                'js-btn-enable bg-green-500 hover:bg-green-600 text-white',
                'bi bi-check-circle',
                'enable', 'Enable'
            );
            if (detailsBtn) {
                actionsContainer.insertBefore(form, detailsBtn);
            } else {
                actionsContainer.appendChild(form);
            }
            form.querySelector('.js-btn-enable').addEventListener('click', handleEnable);
        }
        // Add uninstall button if not unofficial
        if (card.dataset.unofficial !== 'true') {
            const uninstallUrl = card.dataset.uninstallUrl;
            if (uninstallUrl) {
                const uninstallForm = createActionForm(
                    uninstallUrl,
                    'js-btn-uninstall bg-gray-200 dark:bg-slate-700 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-600 dark:text-gray-400',
                    'bi bi-trash',
                    'uninstall', 'Uninstall'
                );
                if (detailsBtn) {
                    actionsContainer.insertBefore(uninstallForm, detailsBtn);
                } else {
                    actionsContainer.appendChild(uninstallForm);
                }
                uninstallForm.querySelector('.js-btn-uninstall').addEventListener('click', handleUninstall);
            }
        }
    } else if (newStatus === 'not_installed') {
        const installUrl = card.dataset.installUrl;
        if (installUrl && card.dataset.activable === 'true') {
            form = createActionForm(
                installUrl,
                'js-btn-install bg-indigo-600 hover:bg-indigo-700 text-white',
                'bi bi-cloud-download',
                'install', 'Install'
            );
            if (detailsBtn) {
                actionsContainer.insertBefore(form, detailsBtn);
            } else {
                actionsContainer.appendChild(form);
            }
            form.querySelector('.js-btn-install').addEventListener('click', handleInstall);
        }
    } else if (newStatus === 'updated') {
        const updateBtn = actionsContainer.querySelector('.js-btn-update');
        if (updateBtn) {
            const uf2 = updateBtn.closest('form');
            if (uf2) uf2.remove(); else updateBtn.remove();
        }
        const wasEnabled = card.dataset.enabled === 'true';
        if (wasEnabled) {
            const disableUrlUpd = card.dataset.disableUrl;
            if (disableUrlUpd) {
                form = createActionForm(
                    disableUrlUpd,
                    'js-btn-disable bg-red-500 hover:bg-red-600 text-white',
                    'bi bi-x-circle',
                    'disable', 'Disable'
                );
                if (detailsBtn) {
                    actionsContainer.insertBefore(form, detailsBtn);
                } else {
                    actionsContainer.appendChild(form);
                }
                form.querySelector('.js-btn-disable').addEventListener('click', handleDisable);
            }
        } else {
            const enableUrlUpd = card.dataset.enableUrl;
            if (enableUrlUpd && card.dataset.activable === 'true') {
                form = createActionForm(
                    enableUrlUpd,
                    'js-btn-enable bg-green-500 hover:bg-green-600 text-white',
                    'bi bi-check-circle',
                    'enable', 'Enable'
                );
                if (detailsBtn) {
                    actionsContainer.insertBefore(form, detailsBtn);
                } else {
                    actionsContainer.appendChild(form);
                }
                form.querySelector('.js-btn-enable').addEventListener('click', handleEnable);
            }
            if (card.dataset.unofficial !== 'true') {
                const uninstallUrlUpd = card.dataset.uninstallUrl;
                if (uninstallUrlUpd) {
                    const uninstallFormUpd = createActionForm(
                        uninstallUrlUpd,
                        'js-btn-uninstall bg-gray-200 dark:bg-slate-700 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-600 dark:text-gray-400',
                        'bi bi-trash',
                        'uninstall', 'Uninstall'
                    );
                    if (detailsBtn) {
                        actionsContainer.insertBefore(uninstallFormUpd, detailsBtn);
                    } else {
                        actionsContainer.appendChild(uninstallFormUpd);
                    }
                    uninstallFormUpd.querySelector('.js-btn-uninstall').addEventListener('click', handleUninstall);
                }
            }
        }
    }
}

// --- Cross-tab card sync ---

function syncGlobalData(uuid, newStatus) {
    if (!uuid || !window.__extensionsData || !window.__extensionsData[uuid]) return;
    const data = window.__extensionsData[uuid];
    switch (newStatus) {
        case 'enabled':
            data.isInstalled = true;
            data.isEnabled = true;
            break;
        case 'updated':
            data.isInstalled = true;
            data.hasUpdate = false;
            break;
        case 'installed':
        case 'disabled':
            data.isInstalled = true;
            data.isEnabled = false;
            break;
    }
}

function syncCardsAcrossTabs(sourceCard, uuid, newStatus) {
    if (!uuid) return;
    document.querySelectorAll('.js-extension-card[data-uuid="' + uuid + '"]').forEach((c) => {
        if (c !== sourceCard) {
            updateCardStatus(c, newStatus);
            replaceActionButtons(c, newStatus);
        }
    });
}

function addCardToInstalledTab(sourceCard) {
    if (!sourceCard) return;
    const uuid = sourceCard.dataset.uuid;
    if (!uuid) return;

    const installedTab = document.getElementById('tab-installed');
    if (!installedTab) return;

    if (installedTab.querySelector('.js-extension-card[data-uuid="' + uuid + '"]')) return;

    const discoverCard = document.querySelector('#tab-discover .js-extension-card[data-uuid="' + uuid + '"]');
    const cardToClone = discoverCard || sourceCard;
    const newCard = cardToClone.cloneNode(true);

    const type = newCard.dataset.type || '';
    const sectionKey = type === 'themes' ? 'themes' : 'modules';
    let section = installedTab.querySelector('[data-section="' + sectionKey + '"]');

    if (!section) {
        Array.from(installedTab.children).forEach((child) => {
            if (!child.hasAttribute('data-section') && !child.classList.contains('js-no-results')) {
                child.classList.add('hidden');
            }
        });
        section = createInstalledSection(sectionKey);
        const noResults = installedTab.querySelector('.js-no-results');
        if (noResults) {
            installedTab.insertBefore(section, noResults);
        } else {
            installedTab.appendChild(section);
        }
    }

    section.style.display = '';
    const grid = section.querySelector('.grid');
    if (!grid) return;

    newCard.style.opacity = '0';
    newCard.style.transform = 'scale(0.95)';
    grid.appendChild(newCard);

    requestAnimationFrame(() => {
        newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        newCard.style.opacity = '1';
        newCard.style.transform = 'scale(1)';
    });

    bindActionHandlers(newCard);

    const remaining = section.querySelectorAll('.js-extension-card');
    const countEl = section.querySelector('.flex.items-center > span.text-sm');
    if (countEl) countEl.textContent = '(' + remaining.length + ')';

    updateInstalledTabCounter();
}

function createInstalledSection(sectionKey) {
    const section = document.createElement('div');
    section.className = 'mb-10';
    section.setAttribute('data-section', sectionKey);

    const header = document.createElement('div');
    header.className = 'flex items-center gap-3 mb-5';

    const iconWrapper = document.createElement('div');
    const icon = document.createElement('i');
    if (sectionKey === 'themes') {
        iconWrapper.className = 'w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg';
        icon.className = 'bi bi-palette-fill text-white text-lg';
    } else {
        iconWrapper.className = 'w-10 h-10 bg-gradient-to-br from-indigo-400 to-blue-500 rounded-xl flex items-center justify-center shadow-lg';
        icon.className = 'bi bi-puzzle-fill text-white text-lg';
    }
    iconWrapper.appendChild(icon);

    const title = document.createElement('h2');
    title.className = 'text-xl font-bold text-gray-900 dark:text-white';
    title.textContent = sectionKey === 'themes'
        ? getTranslation('sections_my_themes', 'My themes')
        : getTranslation('sections_my_modules', 'My modules & addons');

    const count = document.createElement('span');
    count.className = 'text-sm text-gray-500 dark:text-gray-400';
    count.textContent = '(0)';

    header.appendChild(iconWrapper);
    header.appendChild(title);
    header.appendChild(count);

    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5';

    section.appendChild(header);
    section.appendChild(grid);

    return section;
}

// --- Common Handler Logic (DRY) ---

function handleActionSuccess(button, card, json, newStatus, translationKey, fallback) {
    announceResult(json.message || getTranslation(translationKey, fallback));

    const uuid = card ? card.dataset.uuid : null;

    emitEvent('extension:status-changed', {
        uuid,
        type: card ? card.dataset.type : null,
        status: newStatus,
        message: json.message,
    });

    if (card) {
        updateCardStatus(card, newStatus);
        replaceActionButtons(card, newStatus);
        syncGlobalData(uuid, newStatus);
        syncCardsAcrossTabs(card, uuid, newStatus);

        if (newStatus === 'installed' || newStatus === 'enabled') {
            addCardToInstalledTab(card);
        }
    } else {
        hideSpinner(button);
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: json.message || getTranslation(translationKey, fallback),
            timer: 2000,
            showConfirmButton: false,
        });
    }
}

function handleActionError(button, card, err, action, translationKey, hasLicenseCheck) {
    hideSpinner(button);
    announceResult(err.message || getTranslation(translationKey, 'Failed to ' + action + ' extension'));

    if (hasLicenseCheck && err.status === 403 && err.data && err.data.purchase_url) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: getTranslation('license_required', 'License Required'),
                text: err.message || getTranslation('cannot_enable', 'This extension requires a valid license.'),
                confirmButtonText: getTranslation('buy', 'Purchase License'),
                showCancelButton: true,
                cancelButtonText: getTranslation('cancel', 'Cancel'),
                confirmButtonColor: '#4f46e5',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(err.data.purchase_url, '_blank');
                }
            });
        }
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: getTranslation('error', 'Error'),
            text: err.message || getTranslation(translationKey, 'Operation failed'),
        });
    }

    emitEvent('extension:error', {
        uuid: card ? card.dataset.uuid : null,
        action,
        error: err.message,
    });
}

// --- Action Handlers ---

function deactivateOtherThemeCards(activeCard) {
    const cards = document.querySelectorAll('.js-extension-card[data-type="themes"]');
    cards.forEach((otherCard) => {
        if (otherCard === activeCard) return;
        if (otherCard.dataset.enabled !== 'true') return;
        updateCardStatus(otherCard, 'disabled');
        replaceActionButtons(otherCard, 'disabled');
    });
}

function handleEnable(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const card = button.closest('.js-extension-card');
    const url = button.dataset.url || (button.closest('form') && button.closest('form').action);
    if (!url) return;

    showSpinner(button);
    ajaxPost(url)
        .then((json) => {
            handleActionSuccess(button, card, json, 'enabled', 'enabled', 'Extension enabled');
            if (card && card.dataset.type === 'themes') {
                deactivateOtherThemeCards(card);
            }
        })
        .catch((err) => {
            handleActionError(button, card, err, 'enable', 'error_enable', true);
        });
}

function handleDisable(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const card = button.closest('.js-extension-card');
    const url = button.dataset.url || (button.closest('form') && button.closest('form').action);
    const extensionName = (card && card.dataset.name) || 'this extension';
    if (!url) return;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: getTranslation('confirm_disable_title', 'Disable Extension?'),
            html: getTranslation('confirm_disable_text', 'Disabling <strong>{name}</strong> may affect dependent features.').replace('{name}', extensionName),
            confirmButtonText: getTranslation('confirm_disable', 'Disable'),
            showCancelButton: true,
            cancelButtonText: getTranslation('cancel', 'Cancel'),
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) {
                showSpinner(button);
                ajaxPost(url)
                    .then((json) => {
                        handleActionSuccess(button, card, json, 'disabled', 'disabled', 'Extension disabled');
                    })
                    .catch((err) => {
                        handleActionError(button, card, err, 'disable', 'error_disable', false);
                    });
            }
        });
    } else {
        if (confirm(getTranslation('confirm_disable_text', 'Are you sure?'))) {
            showSpinner(button);
            ajaxPost(url)
                .then((json) => {
                    handleActionSuccess(button, card, json, 'disabled', 'disabled', 'Extension disabled');
                })
                .catch((err) => {
                    handleActionError(button, card, err, 'disable', 'error_disable', false);
                });
        }
    }
}

function handleInstall(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const card = button.closest('.js-extension-card');
    const url = button.dataset.url || (button.closest('form') && button.closest('form').action);
    if (!url) return;

    showSpinner(button);
    ajaxPost(url)
        .then((json) => {
            handleActionSuccess(button, card, json, 'installed', 'installed_success', 'Extension installed');
        })
        .catch((err) => {
            handleActionError(button, card, err, 'install', 'error_install', true);
        });
}

// --- Uninstall: card removal helpers ---

function extractUuidFromUrl(url) {
    if (!url) return null;
    const parts = url.split('/');
    const idx = parts.lastIndexOf('uninstall');
    return idx > 0 ? parts[idx - 1] : null;
}

function removeCardFromInstalledTab(uuid) {
    if (!uuid) return;
    const installedTab = document.getElementById('tab-installed');
    if (!installedTab) return;

    installedTab.querySelectorAll('.js-extension-card[data-uuid="' + uuid + '"]').forEach((c) => {
        c.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        c.style.opacity = '0';
        c.style.transform = 'scale(0.95)';
        setTimeout(() => {
            const section = c.closest('[data-section]');
            c.remove();
            if (section) {
                const remaining = section.querySelectorAll('.js-extension-card');
                const countEl = section.querySelector('.flex.items-center > span.text-sm');
                if (countEl) countEl.textContent = '(' + remaining.length + ')';
                if (remaining.length === 0) section.style.display = 'none';
            }
            updateInstalledTabCounter();
        }, 300);
    });
}

function updateInstalledTabCounter() {
    const installedTab = document.getElementById('tab-installed');
    if (!installedTab) return;
    const count = installedTab.querySelectorAll('.js-extension-card').length;
    const counterEl = document.querySelector('.main-tab-btn[data-tab="installed"] .js-tab-counter');
    if (counterEl) counterEl.textContent = count;
}

function handleUninstall(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const card = button.closest('.js-extension-card');
    const url = button.dataset.url || (button.closest('form') && button.closest('form').action);
    const extensionName = (card && card.dataset.name) || 'this extension';
    const uuid = card ? card.dataset.uuid : extractUuidFromUrl(url);
    if (!url) return;

    function performUninstall() {
        showSpinner(button);
        ajaxPost(url)
            .then((json) => {
                announceResult(json.message || getTranslation('uninstalled', 'Extension uninstalled'));
                emitEvent('extension:status-changed', {
                    uuid, status: 'not_installed', message: json.message,
                });

                if (uuid && window.__extensionsData && window.__extensionsData[uuid]) {
                    window.__extensionsData[uuid].isInstalled = false;
                    window.__extensionsData[uuid].isEnabled = false;
                }

                removeCardFromInstalledTab(uuid);

                if (uuid) {
                    document.querySelectorAll(
                        '#tab-discover .js-extension-card[data-uuid="' + uuid + '"],' +
                        '#tab-themes .js-extension-card[data-uuid="' + uuid + '"]'
                    ).forEach((c) => {
                        updateCardStatus(c, 'not_installed');
                        replaceActionButtons(c, 'not_installed');
                    });
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: json.message || getTranslation('uninstalled', 'Extension uninstalled'),
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            })
            .catch((err) => {
                handleActionError(button, card, err, 'uninstall', 'error_uninstall', false);
            });
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: getTranslation('confirm_uninstall_title', 'Uninstall Extension?'),
            html: getTranslation('confirm_uninstall_text', 'Uninstalling <strong>{name}</strong> will remove its files. Data in the database will be preserved.')
                .replace('{name}', extensionName),
            confirmButtonText: getTranslation('confirm_uninstall', 'Uninstall'),
            showCancelButton: true,
            cancelButtonText: getTranslation('cancel', 'Cancel'),
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) performUninstall();
        });
    } else {
        if (confirm(getTranslation('confirm_uninstall_text', 'Are you sure?'))) {
            performUninstall();
        }
    }
}

function handleUpdate(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const card = button.closest('.js-extension-card');
    const url = button.dataset.url || (button.closest('form') && button.closest('form').action);
    if (!url) return;

    showSpinner(button);
    ajaxPost(url)
        .then((json) => {
            handleActionSuccess(button, card, json, 'updated', 'updated', 'Extension updated');
        })
        .catch((err) => {
            handleActionError(button, card, err, 'update', 'error_update', false);
        });
}

// --- Bind all handlers ---

function bindActionHandlers(container) {
    container = container || document;
    container.querySelectorAll('.js-btn-enable').forEach((btn) => {
        btn.addEventListener('click', handleEnable);
    });
    container.querySelectorAll('.js-btn-disable').forEach((btn) => {
        btn.addEventListener('click', handleDisable);
    });
    container.querySelectorAll('.js-btn-install').forEach((btn) => {
        btn.addEventListener('click', handleInstall);
    });
    container.querySelectorAll('.js-btn-update').forEach((btn) => {
        btn.addEventListener('click', handleUpdate);
    });
    container.querySelectorAll('.js-btn-uninstall').forEach((btn) => {
        btn.addEventListener('click', handleUninstall);
    });
}

export {
    handleEnable,
    handleDisable,
    handleInstall,
    handleUpdate,
    handleUninstall,
    showSpinner,
    hideSpinner,
    ajaxPost,
    bindActionHandlers,
};
