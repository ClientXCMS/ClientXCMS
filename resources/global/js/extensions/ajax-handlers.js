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
        if (newStatus !== 'updated') {
            card.setAttribute('data-enabled', newStatus === 'enabled' ? 'true' : 'false');
        }
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
                // Preserve enabled/disabled badge; only remove the update badge
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
                var updateBadge = card.querySelector('.js-update-badge');
                if (updateBadge) updateBadge.remove();
                break;
            case 'not_installed':
                card.setAttribute('data-installed', 'false');
                card.setAttribute('data-enabled', 'false');
                badge.classList.add('hidden');
                break;
        }
    }

    function replaceActionButtons(card, newStatus) {
        var actionsContainer = card.querySelector('.js-card-actions');
        if (!actionsContainer) return;

        // Remove existing action forms (enable/disable/install/uninstall)
        var enableBtn = actionsContainer.querySelector('.js-btn-enable');
        var disableBtn = actionsContainer.querySelector('.js-btn-disable');
        var installBtn = actionsContainer.querySelector('.js-btn-install');
        var uninstallBtn = actionsContainer.querySelector('.js-btn-uninstall');

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
        if (uninstallBtn) {
            var uf = uninstallBtn.closest('form');
            if (uf) uf.remove(); else uninstallBtn.remove();
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
            // Add uninstall button if not unofficial
            if (card.dataset.unofficial !== 'true') {
                var uninstallUrl = card.dataset.uninstallUrl;
                if (uninstallUrl) {
                    var uninstallForm = createActionForm(
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
            var installUrl = card.dataset.installUrl;
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
            // Remove the update button
            var updateBtn = actionsContainer.querySelector('.js-btn-update');
            if (updateBtn) {
                var uf2 = updateBtn.closest('form');
                if (uf2) uf2.remove(); else updateBtn.remove();
            }
            // Restore action buttons based on the extension's enabled/disabled state
            var wasEnabled = card.dataset.enabled === 'true';
            if (wasEnabled) {
                var disableUrlUpd = card.dataset.disableUrl;
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
                var enableUrlUpd = card.dataset.enableUrl;
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
                    var uninstallUrlUpd = card.dataset.uninstallUrl;
                    if (uninstallUrlUpd) {
                        var uninstallFormUpd = createActionForm(
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
        var data = window.__extensionsData[uuid];
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
        document.querySelectorAll('.js-extension-card[data-uuid="' + uuid + '"]').forEach(function (c) {
            if (c !== sourceCard) {
                updateCardStatus(c, newStatus);
                replaceActionButtons(c, newStatus);
            }
        });
    }

    function addCardToInstalledTab(sourceCard) {
        if (!sourceCard) return;
        var uuid = sourceCard.dataset.uuid;
        if (!uuid) return;

        var installedTab = document.getElementById('tab-installed');
        if (!installedTab) return;

        // Skip if already present
        if (installedTab.querySelector('.js-extension-card[data-uuid="' + uuid + '"]')) return;

        // Prefer cloning from #tab-discover (same _card template as installed tab)
        var discoverCard = document.querySelector('#tab-discover .js-extension-card[data-uuid="' + uuid + '"]');
        var cardToClone = discoverCard || sourceCard;
        var newCard = cardToClone.cloneNode(true);

        // Determine section (themes vs modules)
        var type = newCard.dataset.type || '';
        var sectionKey = type === 'themes' ? 'themes' : 'modules';
        var section = installedTab.querySelector('[data-section="' + sectionKey + '"]');

        if (!section) {
            // Hide the "no installed" empty state
            Array.from(installedTab.children).forEach(function (child) {
                if (!child.hasAttribute('data-section') && !child.classList.contains('js-no-results')) {
                    child.classList.add('hidden');
                }
            });
            section = createInstalledSection(sectionKey);
            var noResults = installedTab.querySelector('.js-no-results');
            if (noResults) {
                installedTab.insertBefore(section, noResults);
            } else {
                installedTab.appendChild(section);
            }
        }

        section.style.display = '';
        var grid = section.querySelector('.grid');
        if (!grid) return;

        // Fade-in animation
        newCard.style.opacity = '0';
        newCard.style.transform = 'scale(0.95)';
        grid.appendChild(newCard);

        requestAnimationFrame(function () {
            newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            newCard.style.opacity = '1';
            newCard.style.transform = 'scale(1)';
        });

        bindActionHandlers(newCard);

        // Update section count
        var remaining = section.querySelectorAll('.js-extension-card');
        var countEl = section.querySelector('.flex.items-center > span.text-sm');
        if (countEl) countEl.textContent = '(' + remaining.length + ')';

        updateInstalledTabCounter();
    }

    function createInstalledSection(sectionKey) {
        var section = document.createElement('div');
        section.className = 'mb-10';
        section.setAttribute('data-section', sectionKey);

        var header = document.createElement('div');
        header.className = 'flex items-center gap-3 mb-5';

        var iconWrapper = document.createElement('div');
        var icon = document.createElement('i');
        if (sectionKey === 'themes') {
            iconWrapper.className = 'w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl flex items-center justify-center shadow-lg';
            icon.className = 'bi bi-palette-fill text-white text-lg';
        } else {
            iconWrapper.className = 'w-10 h-10 bg-gradient-to-br from-indigo-400 to-blue-500 rounded-xl flex items-center justify-center shadow-lg';
            icon.className = 'bi bi-puzzle-fill text-white text-lg';
        }
        iconWrapper.appendChild(icon);

        var title = document.createElement('h2');
        title.className = 'text-xl font-bold text-gray-900 dark:text-white';
        title.textContent = sectionKey === 'themes'
            ? getTranslation('sections_my_themes', 'My themes')
            : getTranslation('sections_my_modules', 'My modules & addons');

        var count = document.createElement('span');
        count.className = 'text-sm text-gray-500 dark:text-gray-400';
        count.textContent = '(0)';

        header.appendChild(iconWrapper);
        header.appendChild(title);
        header.appendChild(count);

        var grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5';

        section.appendChild(header);
        section.appendChild(grid);

        return section;
    }

    // --- Common Handler Logic (DRY) ---

    function handleActionSuccess(button, card, json, newStatus, translationKey, fallback) {
        announceResult(json.message || getTranslation(translationKey, fallback));

        var uuid = card ? card.dataset.uuid : null;

        emitEvent('extension:status-changed', {
            uuid: uuid,
            type: card ? card.dataset.type : null,
            status: newStatus,
            message: json.message
        });

        if (card) {
            updateCardStatus(card, newStatus);
            replaceActionButtons(card, newStatus);

            // Sync global data store
            syncGlobalData(uuid, newStatus);

            // Sync duplicate cards across all tabs
            syncCardsAcrossTabs(card, uuid, newStatus);

            // Add card to installed tab when newly installed
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

    function deactivateOtherThemeCards(activeCard) {
        var cards = document.querySelectorAll('.js-extension-card[data-type="themes"]');
        cards.forEach(function (otherCard) {
            if (otherCard === activeCard) return;
            if (otherCard.dataset.enabled !== 'true') return;
            updateCardStatus(otherCard, 'disabled');
            replaceActionButtons(otherCard, 'disabled');
        });
    }

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
                // Themes are mutually exclusive: deactivate others visually
                if (card && card.dataset.type === 'themes') {
                    deactivateOtherThemeCards(card);
                }
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

    // --- Uninstall: card removal helpers ---

    function extractUuidFromUrl(url) {
        if (!url) return null;
        var parts = url.split('/');
        var idx = parts.lastIndexOf('uninstall');
        return idx > 0 ? parts[idx - 1] : null;
    }

    function removeCardFromInstalledTab(uuid) {
        if (!uuid) return;
        var installedTab = document.getElementById('tab-installed');
        if (!installedTab) return;

        installedTab.querySelectorAll('.js-extension-card[data-uuid="' + uuid + '"]').forEach(function (c) {
            c.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            c.style.opacity = '0';
            c.style.transform = 'scale(0.95)';
            setTimeout(function () {
                var section = c.closest('[data-section]');
                c.remove();
                if (section) {
                    var remaining = section.querySelectorAll('.js-extension-card');
                    var countEl = section.querySelector('.flex.items-center > span.text-sm');
                    if (countEl) countEl.textContent = '(' + remaining.length + ')';
                    if (remaining.length === 0) section.style.display = 'none';
                }
                updateInstalledTabCounter();
            }, 300);
        });
    }

    function updateInstalledTabCounter() {
        var installedTab = document.getElementById('tab-installed');
        if (!installedTab) return;
        var count = installedTab.querySelectorAll('.js-extension-card').length;
        var counterEl = document.querySelector('.main-tab-btn[data-tab="installed"] .js-tab-counter');
        if (counterEl) counterEl.textContent = count;
    }

    function handleUninstall(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var card = button.closest('.js-extension-card');
        var url = button.dataset.url || (button.closest('form') && button.closest('form').action);
        var extensionName = (card && card.dataset.name) || 'this extension';
        var uuid = card ? card.dataset.uuid : extractUuidFromUrl(url);
        if (!url) return;

        function performUninstall() {
            showSpinner(button);
            ajaxPost(url)
                .then(function (json) {
                    announceResult(json.message || getTranslation('uninstalled', 'Extension uninstalled'));
                    emitEvent('extension:status-changed', {
                        uuid: uuid, status: 'not_installed', message: json.message
                    });

                    // Sync global data store
                    if (uuid && window.__extensionsData && window.__extensionsData[uuid]) {
                        window.__extensionsData[uuid].isInstalled = false;
                        window.__extensionsData[uuid].isEnabled = false;
                    }

                    // Close modal if triggered from there
                    if (!card && window.ExtensionModal) {
                        window.ExtensionModal.closeModal();
                    }

                    // Remove from installed tab with fade-out animation
                    removeCardFromInstalledTab(uuid);

                    // Update duplicate cards in discover/themes tabs (show Install button)
                    if (uuid) {
                        document.querySelectorAll(
                            '#tab-discover .js-extension-card[data-uuid="' + uuid + '"],' +
                            '#tab-themes .js-extension-card[data-uuid="' + uuid + '"]'
                        ).forEach(function (c) {
                            updateCardStatus(c, 'not_installed');
                            replaceActionButtons(c, 'not_installed');
                        });
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: json.message || getTranslation('uninstalled', 'Extension uninstalled'),
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(function (err) {
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
                cancelButtonColor: '#6b7280'
            }).then(function (result) {
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
        container.querySelectorAll('.js-btn-uninstall').forEach(function (btn) {
            btn.addEventListener('click', handleUninstall);
        });
    }

    // Export for modal reuse
    window.ExtensionActions = {
        handleEnable: handleEnable,
        handleDisable: handleDisable,
        handleInstall: handleInstall,
        handleUpdate: handleUpdate,
        handleUninstall: handleUninstall,
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
