/**
 * Extensions AJAX Action Handlers (Stories 1.5 & 1.6)
 *
 * Wires click handlers on enable/disable/install/update buttons
 * to call backend AJAX endpoints without page reload.
 *
 * Uses SweetAlert2 for confirmations and error display.
 * Emits extension:* CustomEvents for cross-module communication.
 */
(function () {
    'use strict';

    var CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') || ''
        : '';

    // --- Utility Functions ---

    function getTranslation(key, fallback) {
        return (window.__extensionTranslations && window.__extensionTranslations[key]) || fallback;
    }

    function createSpinnerElement() {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'animate-spin h-4 w-4');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('viewBox', '0 0 24 24');
        var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', 'opacity-25');
        circle.setAttribute('cx', '12');
        circle.setAttribute('cy', '12');
        circle.setAttribute('r', '10');
        circle.setAttribute('stroke', 'currentColor');
        circle.setAttribute('stroke-width', '4');
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'opacity-75');
        path.setAttribute('fill', 'currentColor');
        path.setAttribute('d', 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z');
        svg.appendChild(circle);
        svg.appendChild(path);
        return svg;
    }

    function showSpinner(button) {
        button._originalNodes = Array.from(button.childNodes).map(function(n) { return n.cloneNode(true); });
        button._originalDisabled = button.disabled;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        // Build spinner DOM safely
        button.textContent = '';
        var span = document.createElement('span');
        span.className = 'inline-flex items-center gap-2';
        span.appendChild(createSpinnerElement());
        var textNode = document.createTextNode(' ' + getTranslation('processing', 'Processing...'));
        span.appendChild(textNode);
        button.appendChild(span);
    }

    function hideSpinner(button) {
        button.disabled = button._originalDisabled || false;
        button.removeAttribute('aria-busy');
        // Restore original content safely (was captured from server-rendered DOM)
        if (button._originalNodes) {
            button.textContent = '';
            button._originalNodes.forEach(function(n) { button.appendChild(n.cloneNode(true)); });
        }
    }

    function announceResult(message) {
        var region = document.getElementById('js-extension-announcer');
        if (region) {
            region.textContent = message;
        }
    }

    function emitEvent(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail: detail, bubbles: true }));
    }

    // --- AJAX ---

    function ajaxPost(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: new URLSearchParams({ _token: CSRF_TOKEN })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) {
                    var error = new Error(json.message || 'Request failed');
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
        var form = document.createElement('form');
        form.action = actionUrl;
        form.method = 'POST';

        var tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = CSRF_TOKEN;
        form.appendChild(tokenInput);

        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.className = buttonClass + ' w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0';

        var icon = document.createElement('i');
        icon.className = iconClass;
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode(getTranslation(labelKey, labelFallback)));

        form.appendChild(btn);
        return form;
    }

    // --- Card DOM Update ---

    function updateCardStatus(card, newStatus) {
        if (!card) return;

        // Sync all data-* attributes for DOM/store consistency
        card.setAttribute('data-status', newStatus);
        card.setAttribute('data-enabled', newStatus === 'enabled' ? 'true' : 'false');
        if (newStatus !== 'not_installed') {
            card.setAttribute('data-installed', 'true');
        }
        if (newStatus === 'updated') {
            card.setAttribute('data-has-update', 'false');
        }

        var badge = card.querySelector('.js-status-badge');
        if (!badge) return;

        badge.classList.remove(
            'bg-green-500', 'bg-blue-500', 'bg-amber-500',
            'dark:bg-green-600', 'dark:bg-blue-600', 'dark:bg-amber-600'
        );

        var icon = badge.querySelector('i');
        var text = badge.querySelector('.js-status-text');

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
                badge.classList.add('bg-green-500');
                badge.classList.remove('hidden');
                if (icon) { icon.className = 'bi bi-check-circle-fill mr-1'; }
                if (text) { text.textContent = getTranslation('enabled', 'Enabled'); }
                var updateBadge = card.querySelector('.js-update-badge');
                if (updateBadge) updateBadge.remove();
                break;
        }
    }

    function replaceActionButtons(card, newStatus) {
        var actionsContainer = card.querySelector('.js-card-actions');
        if (!actionsContainer) return;

        // Remove existing action forms (enable/disable/install)
        var enableBtn = actionsContainer.querySelector('.js-btn-enable');
        var disableBtn = actionsContainer.querySelector('.js-btn-disable');
        var installBtn = actionsContainer.querySelector('.js-btn-install');

        if (enableBtn) {
            var ef = enableBtn.closest('form');
            if (ef) ef.remove(); else enableBtn.remove();
        }
        if (disableBtn) {
            var df = disableBtn.closest('form');
            if (df) df.remove(); else disableBtn.remove();
        }
        if (installBtn) {
            var inf = installBtn.closest('form');
            if (inf) inf.remove(); else installBtn.remove();
        }

        var detailsBtn = actionsContainer.querySelector('.js-btn-details');
        var form;

        if (newStatus === 'enabled') {
            var disableUrl = card.dataset.disableUrl;
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
            var enableUrl = card.dataset.enableUrl;
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
        } else if (newStatus === 'updated') {
            var updateBtn = actionsContainer.querySelector('.js-btn-update');
            if (updateBtn) {
                var uf = updateBtn.closest('form');
                if (uf) uf.remove(); else updateBtn.remove();
            }
        }
    }

    // --- Common Handler Logic (DRY) ---

    function handleActionSuccess(button, card, json, newStatus, translationKey, fallback) {
        announceResult(json.message || getTranslation(translationKey, fallback));
        emitEvent('extension:status-changed', {
            uuid: card ? card.dataset.uuid : null,
            type: card ? card.dataset.type : null,
            status: newStatus,
            message: json.message
        });

        if (card) {
            updateCardStatus(card, newStatus);
            replaceActionButtons(card, newStatus);
        } else {
            hideSpinner(button);
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: json.message || getTranslation(translationKey, fallback),
                timer: 2000,
                showConfirmButton: false
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
                    confirmButtonColor: '#4f46e5'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.open(err.data.purchase_url, '_blank');
                    }
                });
            }
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: getTranslation('error', 'Error'),
                text: err.message || getTranslation(translationKey, 'Operation failed')
            });
        }

        emitEvent('extension:error', {
            uuid: card ? card.dataset.uuid : null,
            action: action,
            error: err.message
        });
    }

    // --- Action Handlers ---

    function handleEnable(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var card = button.closest('.js-extension-card');
        var url = button.dataset.url || (button.closest('form') && button.closest('form').action);
        if (!url) return;

        showSpinner(button);
        ajaxPost(url)
            .then(function (json) {
                handleActionSuccess(button, card, json, 'enabled', 'enabled', 'Extension enabled');
            })
            .catch(function (err) {
                handleActionError(button, card, err, 'enable', 'error_enable', true);
            });
    }

    function handleDisable(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var card = button.closest('.js-extension-card');
        var url = button.dataset.url || (button.closest('form') && button.closest('form').action);
        var extensionName = (card && card.dataset.name) || 'this extension';
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
                cancelButtonColor: '#6b7280'
            }).then(function (result) {
                if (result.isConfirmed) {
                    showSpinner(button);
                    ajaxPost(url)
                        .then(function (json) {
                            handleActionSuccess(button, card, json, 'disabled', 'disabled', 'Extension disabled');
                        })
                        .catch(function (err) {
                            handleActionError(button, card, err, 'disable', 'error_disable', false);
                        });
                }
            });
        } else {
            if (confirm(getTranslation('confirm_disable_text', 'Are you sure?'))) {
                showSpinner(button);
                ajaxPost(url)
                    .then(function (json) {
                        handleActionSuccess(button, card, json, 'disabled', 'disabled', 'Extension disabled');
                    })
                    .catch(function (err) {
                        handleActionError(button, card, err, 'disable', 'error_disable', false);
                    });
            }
        }
    }

    function handleInstall(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var card = button.closest('.js-extension-card');
        var url = button.dataset.url || (button.closest('form') && button.closest('form').action);
        if (!url) return;

        showSpinner(button);
        ajaxPost(url)
            .then(function (json) {
                handleActionSuccess(button, card, json, 'installed', 'installed_success', 'Extension installed');
            })
            .catch(function (err) {
                handleActionError(button, card, err, 'install', 'error_install', true);
            });
    }

    function handleUpdate(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var card = button.closest('.js-extension-card');
        var url = button.dataset.url || (button.closest('form') && button.closest('form').action);
        if (!url) return;

        showSpinner(button);
        ajaxPost(url)
            .then(function (json) {
                handleActionSuccess(button, card, json, 'updated', 'updated', 'Extension updated');
            })
            .catch(function (err) {
                handleActionError(button, card, err, 'update', 'error_update', false);
            });
    }

    // --- Bind all handlers ---

    function bindActionHandlers(container) {
        container = container || document;
        container.querySelectorAll('.js-btn-enable').forEach(function (btn) {
            btn.addEventListener('click', handleEnable);
        });
        container.querySelectorAll('.js-btn-disable').forEach(function (btn) {
            btn.addEventListener('click', handleDisable);
        });
        container.querySelectorAll('.js-btn-install').forEach(function (btn) {
            btn.addEventListener('click', handleInstall);
        });
        container.querySelectorAll('.js-btn-update').forEach(function (btn) {
            btn.addEventListener('click', handleUpdate);
        });
    }

    // Export for modal reuse
    window.ExtensionActions = {
        handleEnable: handleEnable,
        handleDisable: handleDisable,
        handleInstall: handleInstall,
        handleUpdate: handleUpdate,
        showSpinner: showSpinner,
        hideSpinner: hideSpinner,
        ajaxPost: ajaxPost,
        bindActionHandlers: bindActionHandlers
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindActionHandlers(document); });
    } else {
        bindActionHandlers(document);
    }
})();
