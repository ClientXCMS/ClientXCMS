/**
 * Extensions Product Detail Modal (Story 2.3)
 *
 * Preline UI compatible modal for displaying extension details.
 * Uses focus trap and keyboard navigation.
 * All content rendering uses DOM API (createElement/textContent)
 * instead of innerHTML to prevent XSS.
 */
import { getTranslation, isSafeUrl, el, createFocusTrap } from './utils.js';
import {
    handleEnable,
    handleDisable,
    handleInstall,
    handleUpdate,
    handleUninstall,
} from './ajax-handlers.js';

const MODAL_ID = 'js-extension-modal';
let previousFocusElement = null;
let focusTrap = null;

function getModal() {
    return document.getElementById(MODAL_ID);
}

function announce(message) {
    const announcer = document.getElementById('js-extension-announcer');
    if (announcer) {
        announcer.textContent = message;
    }
}

function openModal(extensionData) {
    const modal = getModal();
    if (!modal) return;

    previousFocusElement = document.activeElement;

    populateModal(modal, extensionData);

    modal.classList.remove('hidden');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    modal.setAttribute('aria-modal', 'true');
    const label = getTranslation('product_sheet', 'Product Sheet') + ' ' + (extensionData.name || '');
    modal.setAttribute('aria-label', label);

    announce(label);

    requestAnimationFrame(() => {
        const firstFocusable = modal.querySelector('button:not([disabled]), a[href], input:not([disabled])');
        if (firstFocusable) firstFocusable.focus();
    });

    focusTrap = createFocusTrap(modal);
    focusTrap.activate();
    document.addEventListener('keydown', handleEscapeKey);
}

function closeModal() {
    const modal = getModal();
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('open');
    document.body.style.overflow = '';

    if (focusTrap) {
        focusTrap.deactivate();
        focusTrap = null;
    }
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

function renderStarsInto(container, rating, reviewCount) {
    while (container.firstChild) container.removeChild(container.firstChild);

    if (reviewCount > 0) {
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        for (let i = 0; i < 5; i++) {
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
    const title = modal.querySelector('.js-modal-title');
    const thumbnail = modal.querySelector('.js-modal-thumbnail');
    const description = modal.querySelector('.js-modal-description');
    const author = modal.querySelector('.js-modal-author');
    const authorAvatar = modal.querySelector('.js-modal-author-avatar');
    const rating = modal.querySelector('.js-modal-rating');
    const version = modal.querySelector('.js-modal-version');
    const updatedAt = modal.querySelector('.js-modal-updated');
    const price = modal.querySelector('.js-modal-price');
    const docLink = modal.querySelector('.js-modal-doc-link');
    const actionsContainer = modal.querySelector('.js-modal-actions');
    const tags = modal.querySelector('.js-modal-tags');

    if (title) title.textContent = data.name || '';

    if (thumbnail) {
        while (thumbnail.firstChild) thumbnail.removeChild(thumbnail.firstChild);
        if (data.thumbnail) {
            const img = document.createElement('img');
            img.src = data.thumbnail;
            img.alt = data.name || '';
            img.className = 'w-full h-full object-cover rounded-xl';
            thumbnail.appendChild(img);
        } else {
            const fallback = el('div', 'w-full h-full flex items-center justify-center bg-gray-100 dark:bg-slate-800 rounded-xl', [
                el('i', 'bi bi-puzzle text-4xl text-gray-300 dark:text-slate-600'),
            ]);
            thumbnail.appendChild(fallback);
        }
    }

    if (description) description.textContent = data.description || '';
    if (author) author.textContent = data.author || 'Unknown';

    if (authorAvatar) {
        while (authorAvatar.firstChild) authorAvatar.removeChild(authorAvatar.firstChild);
        if (data.authorAvatar) {
            const avatarImg = document.createElement('img');
            avatarImg.src = data.authorAvatar;
            avatarImg.alt = data.author || '';
            avatarImg.className = 'w-8 h-8 rounded-full';
            authorAvatar.appendChild(avatarImg);
        } else {
            const initials = (data.author || 'U').charAt(0).toUpperCase();
            authorAvatar.appendChild(
                el('div', 'w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm', initials)
            );
        }
    }

    if (rating) {
        const ratingVal = parseFloat(data.rating) || 0;
        const reviewCount = parseInt(data.reviewCount, 10) || 0;
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
        (data.tags || []).forEach((tag) => {
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
    const enableBtn = container.querySelector('.js-btn-enable');
    const disableBtn = container.querySelector('.js-btn-disable');
    const installBtn = container.querySelector('.js-btn-install');
    const updateBtn = container.querySelector('.js-btn-update');
    const uninstallBtn = container.querySelector('.js-btn-uninstall');

    if (enableBtn) enableBtn.addEventListener('click', handleEnable);
    if (disableBtn) disableBtn.addEventListener('click', handleDisable);
    if (installBtn) installBtn.addEventListener('click', handleInstall);
    if (updateBtn) updateBtn.addEventListener('click', handleUpdate);
    if (uninstallBtn) uninstallBtn.addEventListener('click', handleUninstall);
}

function createActionBtn(cssClass, jsClass, iconClass, label, dataUrl) {
    const btn = document.createElement('button');
    btn.type = 'button';
    const hasTextColor = /\btext-(gray|slate|red|white|black)/.test(cssClass);
    btn.className = jsClass + ' inline-flex items-center gap-2 px-5 py-3 ' + cssClass + (hasTextColor ? '' : ' text-white') + ' rounded-xl font-medium transition-colors';
    if (dataUrl && isSafeUrl(dataUrl)) btn.dataset.url = dataUrl;
    btn.appendChild(el('i', iconClass));
    btn.appendChild(document.createTextNode(' ' + label));
    return btn;
}

function createActionLink(cssClass, iconClass, label, href) {
    const link = document.createElement('a');
    link.href = href;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'inline-flex items-center gap-2 px-5 py-3 ' + cssClass + ' text-white rounded-xl font-medium transition-colors';
    link.appendChild(el('i', iconClass));
    link.appendChild(document.createTextNode(' ' + label));
    return link;
}

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
    document.addEventListener('click', (e) => {
        const detailBtn = e.target.closest('.js-btn-details');
        if (!detailBtn) return;

        e.preventDefault();

        const card = detailBtn.closest('.js-extension-card');
        if (!card) return;

        const extensionData = {
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
    const modal = getModal();
    if (modal) {
        const closeBtn = modal.querySelector('.js-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                closeModal();
            });
        }

        modal.addEventListener('click', (e) => {
            const panel = modal.querySelector('.js-modal-panel');
            if (panel && !panel.contains(e.target)) {
                closeModal();
            }
        });
    }

    // Close modal when an extension is uninstalled (avoids circular dependency with ajax-handlers)
    document.addEventListener('extension:status-changed', (e) => {
        if (e.detail?.status === 'not_installed') {
            closeModal();
        }
    });
}

export { openModal, closeModal, init as initModal };
