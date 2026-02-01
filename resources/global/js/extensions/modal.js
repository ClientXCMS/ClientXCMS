/**
 * Extensions Product Detail Modal (Story 2.3)
 *
 * Preline UI compatible modal for displaying extension details.
 * Uses focus trap and keyboard navigation.
 * All content rendering uses DOM API (createElement/textContent)
 * instead of innerHTML to prevent XSS.
 */
(function () {
    'use strict';

    var MODAL_ID = 'js-extension-modal';
    var FOCUSABLE_SELECTOR = 'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    var previousFocusElement = null;
    var focusTrapHandler = null;

    function getTranslation(key, fallback) {
        return window.__extensionTranslations?.[key] || fallback;
    }

    // Validate URLs to prevent javascript: protocol injection
    function isSafeUrl(url) {
        if (!url) return false;
        var str = String(url).trim();
        return str.startsWith('/') || str.startsWith('http://') || str.startsWith('https://');
    }

    function getModal() {
        return document.getElementById(MODAL_ID);
    }

    function announce(message) {
        var announcer = document.getElementById('js-extension-announcer');
        if (announcer) {
            announcer.textContent = message;
        }
    }

    // --- DOM helper: create an element with class, optional text, optional children ---
    function el(tag, className, textOrChildren) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (typeof textOrChildren === 'string') {
            node.textContent = textOrChildren;
        } else if (Array.isArray(textOrChildren)) {
            textOrChildren.forEach(function (child) {
                if (child) node.appendChild(child);
            });
        }
        return node;
    }

    function openModal(extensionData) {
        var modal = getModal();
        if (!modal) return;

        previousFocusElement = document.activeElement;

        populateModal(modal, extensionData);

        modal.classList.remove('hidden');
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        modal.setAttribute('aria-modal', 'true');
        var label = getTranslation('product_sheet', 'Product Sheet') + ' ' + (extensionData.name || '');
        modal.setAttribute('aria-label', label);

        // Announce modal opening for screen readers via aria-live region
        announce(label);

        requestAnimationFrame(function () {
            var firstFocusable = modal.querySelector(FOCUSABLE_SELECTOR);
            if (firstFocusable) firstFocusable.focus();
        });

        enableFocusTrap(modal);
        document.addEventListener('keydown', handleEscapeKey);
    }

    function closeModal() {
        var modal = getModal();
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('open');
        document.body.style.overflow = '';

        disableFocusTrap();
        document.removeEventListener('keydown', handleEscapeKey);

        if (previousFocusElement) {
            previousFocusElement.focus();
            previousFocusElement = null;
        }
    }

    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    }

    // Focus trap re-queries focusable elements on each Tab press
    // so the trap stays valid after dynamic content changes (action buttons)
    function enableFocusTrap(modal) {
        focusTrapHandler = function (e) {
            if (e.key !== 'Tab') return;

            var focusableElements = modal.querySelectorAll(FOCUSABLE_SELECTOR);
            if (focusableElements.length === 0) return;

            var firstEl = focusableElements[0];
            var lastEl = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === firstEl) {
                    e.preventDefault();
                    lastEl.focus();
                }
            } else {
                if (document.activeElement === lastEl) {
                    e.preventDefault();
                    firstEl.focus();
                }
            }
        };

        modal.addEventListener('keydown', focusTrapHandler);
    }

    function disableFocusTrap() {
        var modal = getModal();
        if (modal && focusTrapHandler) {
            modal.removeEventListener('keydown', focusTrapHandler);
            focusTrapHandler = null;
        }
    }

    function renderStarsInto(container, rating, reviewCount) {
        while (container.firstChild) container.removeChild(container.firstChild);

        if (reviewCount > 0) {
            var fullStars = Math.floor(rating);
            var halfStar = rating % 1 >= 0.5;
            for (var i = 0; i < 5; i++) {
                if (i < fullStars) {
                    container.appendChild(el('i', 'bi bi-star-fill text-yellow-500'));
                } else if (i === fullStars && halfStar) {
                    container.appendChild(el('i', 'bi bi-star-half text-yellow-500'));
                } else {
                    container.appendChild(el('i', 'bi bi-star text-gray-300 dark:text-slate-600'));
                }
            }
            container.appendChild(
                el('span', 'ml-1 text-sm text-gray-500 dark:text-gray-400', '(' + reviewCount + ')')
            );
        } else {
            container.appendChild(
                el('span', 'text-sm text-gray-400 dark:text-gray-500', getTranslation('no_reviews', 'No reviews'))
            );
        }
    }

    function populateModal(modal, data) {
        var title = modal.querySelector('.js-modal-title');
        var thumbnail = modal.querySelector('.js-modal-thumbnail');
        var description = modal.querySelector('.js-modal-description');
        var author = modal.querySelector('.js-modal-author');
        var authorAvatar = modal.querySelector('.js-modal-author-avatar');
        var rating = modal.querySelector('.js-modal-rating');
        var version = modal.querySelector('.js-modal-version');
        var updatedAt = modal.querySelector('.js-modal-updated');
        var price = modal.querySelector('.js-modal-price');
        var docLink = modal.querySelector('.js-modal-doc-link');
        var actionsContainer = modal.querySelector('.js-modal-actions');
        var tags = modal.querySelector('.js-modal-tags');

        if (title) title.textContent = data.name || '';

        if (thumbnail) {
            while (thumbnail.firstChild) thumbnail.removeChild(thumbnail.firstChild);
            if (data.thumbnail) {
                var img = document.createElement('img');
                img.src = data.thumbnail;
                img.alt = data.name || '';
                img.className = 'w-full h-full object-cover rounded-xl';
                thumbnail.appendChild(img);
            } else {
                var fallback = el('div', 'w-full h-full flex items-center justify-center bg-gray-100 dark:bg-slate-800 rounded-xl', [
                    el('i', 'bi bi-puzzle text-4xl text-gray-300 dark:text-slate-600')
                ]);
                thumbnail.appendChild(fallback);
            }
        }

        if (description) description.textContent = data.description || '';
        if (author) author.textContent = data.author || 'Unknown';

        // Author avatar: real image if available, otherwise initials
        if (authorAvatar) {
            while (authorAvatar.firstChild) authorAvatar.removeChild(authorAvatar.firstChild);
            if (data.authorAvatar) {
                var avatarImg = document.createElement('img');
                avatarImg.src = data.authorAvatar;
                avatarImg.alt = data.author || '';
                avatarImg.className = 'w-8 h-8 rounded-full';
                authorAvatar.appendChild(avatarImg);
            } else {
                var initials = (data.author || 'U').charAt(0).toUpperCase();
                authorAvatar.appendChild(
                    el('div', 'w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm', initials)
                );
            }
        }

        if (rating) {
            var ratingVal = parseFloat(data.rating) || 0;
            var reviewCount = parseInt(data.reviewCount, 10) || 0;
            renderStarsInto(rating, ratingVal, reviewCount);
        }

        if (version) version.textContent = data.version || '-';
        if (updatedAt) updatedAt.textContent = data.updatedAt || '-';

        if (price) {
            price.textContent = data.price || getTranslation('free', 'Free');
            price.className = 'js-modal-price text-xl font-bold ' + (data.priceRaw > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-green-600 dark:text-green-400');
        }

        if (docLink) {
            if (data.docUrl && isSafeUrl(data.docUrl)) {
                docLink.href = data.docUrl;
                docLink.classList.remove('hidden');
            } else {
                docLink.href = '#';
                docLink.classList.add('hidden');
            }
        }

        if (tags) {
            while (tags.firstChild) tags.removeChild(tags.firstChild);
            (data.tags || []).forEach(function (tag) {
                tags.appendChild(
                    el('span', 'inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400', tag)
                );
            });
        }

        if (actionsContainer) {
            while (actionsContainer.firstChild) actionsContainer.removeChild(actionsContainer.firstChild);
            buildModalActions(actionsContainer, data);
            wireActionHandlers(actionsContainer);
        }
    }

    function wireActionHandlers(container) {
        var enableBtn = container.querySelector('.js-btn-enable');
        var disableBtn = container.querySelector('.js-btn-disable');
        var installBtn = container.querySelector('.js-btn-install');
        var updateBtn = container.querySelector('.js-btn-update');

        if (enableBtn && window.ExtensionActions) {
            enableBtn.addEventListener('click', window.ExtensionActions.handleEnable);
        }
        if (disableBtn && window.ExtensionActions) {
            disableBtn.addEventListener('click', window.ExtensionActions.handleDisable);
        }
        if (installBtn && window.ExtensionActions) {
            installBtn.addEventListener('click', window.ExtensionActions.handleInstall);
        }
        if (updateBtn && window.ExtensionActions) {
            updateBtn.addEventListener('click', window.ExtensionActions.handleUpdate);
        }

        var uninstallBtn = container.querySelector('.js-btn-uninstall');
        if (uninstallBtn && window.ExtensionActions) {
            uninstallBtn.addEventListener('click', window.ExtensionActions.handleUninstall);
        }
    }

    // Creates a styled action button via DOM API
    function createActionBtn(cssClass, jsClass, iconClass, label, dataUrl) {
        var btn = document.createElement('button');
        btn.type = 'button';
        var hasTextColor = /\btext-(gray|slate|red|white|black)/.test(cssClass);
        btn.className = jsClass + ' inline-flex items-center gap-2 px-5 py-3 ' + cssClass + (hasTextColor ? '' : ' text-white') + ' rounded-xl font-medium transition-colors';
        if (dataUrl && isSafeUrl(dataUrl)) btn.dataset.url = dataUrl;
        btn.appendChild(el('i', iconClass));
        btn.appendChild(document.createTextNode(' ' + label));
        return btn;
    }

    // Creates a styled action link via DOM API
    function createActionLink(cssClass, iconClass, label, href) {
        var link = document.createElement('a');
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'inline-flex items-center gap-2 px-5 py-3 ' + cssClass + ' text-white rounded-xl font-medium transition-colors';
        link.appendChild(el('i', iconClass));
        link.appendChild(document.createTextNode(' ' + label));
        return link;
    }

    // All action buttons built via DOM API -- no innerHTML
    function buildModalActions(container, data) {
        if (data.hasUpdate) {
            container.appendChild(
                createActionBtn('bg-amber-500 hover:bg-amber-600', 'js-btn-update', 'bi bi-download', getTranslation('update', 'Update'), data.updateUrl)
            );
        }

        if (data.isEnabled && data.isActivable) {
            container.appendChild(
                createActionBtn('bg-red-500 hover:bg-red-600', 'js-btn-disable', 'bi bi-x-circle', getTranslation('disable', 'Disable'), data.disableUrl)
            );
        } else if (data.isInstalled && !data.isEnabled && data.isActivable) {
            container.appendChild(
                createActionBtn('bg-green-500 hover:bg-green-600', 'js-btn-enable', 'bi bi-check-circle', getTranslation('enable', 'Enable'), data.enableUrl)
            );
        }

        // Uninstall button: installed + disabled + not unofficial
        if (data.isInstalled && !data.isEnabled && !data.isUnofficial) {
            container.appendChild(
                createActionBtn(
                    'bg-gray-200 dark:bg-slate-700 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-600 dark:text-gray-400',
                    'js-btn-uninstall',
                    'bi bi-trash',
                    getTranslation('uninstall', 'Uninstall'),
                    data.uninstallUrl
                )
            );
        }

        if (!data.isInstalled && data.isActivable) {
            container.appendChild(
                createActionBtn('bg-indigo-600 hover:bg-indigo-700', 'js-btn-install', 'bi bi-cloud-download', getTranslation('install', 'Install'), data.installUrl)
            );
        } else if (data.purchaseUrl && isSafeUrl(data.purchaseUrl)) {
            container.appendChild(
                createActionLink('bg-indigo-600 hover:bg-indigo-700', 'bi bi-bag', getTranslation('buy', 'Buy'), data.purchaseUrl)
            );
        }
    }

    function init() {
        // Detail button clicks via event delegation
        document.addEventListener('click', function (e) {
            var detailBtn = e.target.closest('.js-btn-details');
            if (!detailBtn) return;

            e.preventDefault();

            var card = detailBtn.closest('.js-extension-card');
            if (!card) return;

            var extensionData = {
                uuid: card.dataset.uuid || '',
                type: card.dataset.type || '',
                name: card.dataset.name || '',
                description: card.dataset.description || '',
                author: card.dataset.author || '',
                authorAvatar: card.dataset.authorAvatar || '',
                thumbnail: card.dataset.thumbnail || '',
                version: card.dataset.version || '',
                price: card.dataset.price || '',
                priceRaw: parseFloat(card.dataset.priceRaw || '0'),
                rating: parseFloat(card.dataset.rating || '0'),
                reviewCount: parseInt(card.dataset.reviewCount || '0', 10),
                updatedAt: card.dataset.updatedAt || '',
                docUrl: card.dataset.docUrl || '',
                purchaseUrl: card.dataset.purchaseUrl || '',
                isInstalled: card.dataset.installed === 'true',
                isEnabled: card.dataset.enabled === 'true',
                isActivable: card.dataset.activable === 'true',
                hasUpdate: card.dataset.hasUpdate === 'true',
                enableUrl: card.dataset.enableUrl || '',
                disableUrl: card.dataset.disableUrl || '',
                installUrl: card.dataset.installUrl || '',
                updateUrl: card.dataset.updateUrl || '',
                uninstallUrl: card.dataset.uninstallUrl || '',
                isUnofficial: card.dataset.unofficial === 'true',
                tags: (card.dataset.tagNames || '').split(',').filter(Boolean),
            };

            openModal(extensionData);
        });

        // Modal close handlers: close button + backdrop click
        var modal = getModal();
        if (modal) {
            // Direct handler on close button
            var closeBtn = modal.querySelector('.js-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    closeModal();
                });
            }

            // Backdrop click: close if click lands outside the modal panel
            modal.addEventListener('click', function (e) {
                var panel = modal.querySelector('.js-modal-panel');
                if (panel && !panel.contains(e.target)) {
                    closeModal();
                }
            });
        }
    }

    window.ExtensionModal = { openModal: openModal, closeModal: closeModal };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
