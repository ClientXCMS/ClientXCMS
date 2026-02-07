class ExtensionManager {
    constructor(config) {
        this.csrfToken = config.csrfToken;
        this.routes = config.routes;
        this.translations = config.translations;

        this.init();
    }

    init() {
        this.initToast();
        this.initTabs();
        this.initSearch();
        this.initBulkActions();
        this.initAjaxActions();
        this.initModal();
    }

    initToast() {
        this.toastContainer = document.getElementById('toast-container');
    }

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle';

        toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-fade-in`;
        toast.innerHTML = `<i class="bi bi-${icon}"></i><span>${message}</span>`;

        this.toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    initTabs() {
        const mainTabBtns = document.querySelectorAll('.main-tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        mainTabBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const tabId = this.dataset.tab;

                mainTabBtns.forEach(b => {
                    b.classList.remove('active', 'bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'dark:text-indigo-400', 'shadow-sm');
                    b.classList.add('text-gray-600', 'dark:text-gray-400');
                    const badge = b.querySelector('span:last-child');
                    if (badge) {
                        badge.classList.remove('bg-indigo-100', 'dark:bg-indigo-900/50', 'text-indigo-600', 'dark:text-indigo-400');
                        badge.classList.add('bg-gray-200', 'dark:bg-slate-600', 'text-gray-600', 'dark:text-gray-400');
                    }
                });

                this.classList.add('active', 'bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'dark:text-indigo-400', 'shadow-sm');
                this.classList.remove('text-gray-600', 'dark:text-gray-400');
                const activeBadge = this.querySelector('span:last-child');
                if (activeBadge) {
                    activeBadge.classList.add('bg-indigo-100', 'dark:bg-indigo-900/50', 'text-indigo-600', 'dark:text-indigo-400');
                    activeBadge.classList.remove('bg-gray-200', 'dark:bg-slate-600', 'text-gray-600', 'dark:text-gray-400');
                }

                tabContents.forEach(content => {
                    if (content.id === `tab-${tabId}`) {
                        content.classList.remove('hidden');
                        content.style.animation = 'fadeIn 0.3s ease forwards';
                    } else {
                        content.classList.add('hidden');
                    }
                });
            });
        });
    }
    initSearch() {
        const searchInput = document.getElementById('extension-search');
        const groupFilterBtns = document.querySelectorAll('.group-filter-btn');
        const noResults = document.getElementById('no-results');
        const extensionsGrid = document.getElementById('extensions-grid');
        const extensionsCount = document.getElementById('extensions-count');
        const discoverExtensionItems = document.querySelectorAll('#extensions-grid .extension-item');

        let activeGroup = 'all';
        let searchQuery = '';

        const filterExtensions = () => {
            let visibleCount = 0;

            if (searchQuery !== '') {
                const discoverTabBtn = document.querySelector('.main-tab-btn[data-tab="discover"]');
                if (discoverTabBtn && !discoverTabBtn.classList.contains('active')) {
                    discoverTabBtn.click();
                }

                discoverExtensionItems.forEach(item => {
                    const category = item.dataset.category;
                    const name = item.dataset.name?.toLowerCase() || '';
                    const description = item.dataset.description?.toLowerCase() || '';

                    const matchesGroup = activeGroup === 'all' || category === activeGroup;
                    const matchesSearch = name.includes(searchQuery.toLowerCase()) || description.includes(searchQuery.toLowerCase());

                    if (matchesGroup && matchesSearch) {
                        item.classList.remove('hidden');
                        item.style.animation = 'fadeIn 0.3s ease forwards';
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                document.querySelectorAll('#section-featured, #section-new, #section-popular').forEach(section => {
                    if (section) section.classList.add('hidden');
                });
            } else {
                discoverExtensionItems.forEach(item => {
                    const category = item.dataset.category;
                    const matchesGroup = activeGroup === 'all' || category === activeGroup;

                    if (matchesGroup) {
                        item.classList.remove('hidden');
                        item.style.animation = 'fadeIn 0.3s ease forwards';
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                document.querySelectorAll('#section-featured, #section-new, #section-popular').forEach(section => {
                    if (section) {
                        section.classList.toggle('hidden', activeGroup !== 'all');
                    }
                });
            }

            if (extensionsCount) {
                extensionsCount.textContent = `${visibleCount} extension${visibleCount !== 1 ? 's' : ''}`;
            }

            if (noResults && extensionsGrid) {
                noResults.classList.toggle('hidden', !(visibleCount === 0 && searchQuery !== ''));
                extensionsGrid.classList.toggle('hidden', visibleCount === 0 && searchQuery !== '');
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                searchQuery = this.value.trim();
                filterExtensions();
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    searchQuery = '';
                    filterExtensions();
                }
            });
        }

        groupFilterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                groupFilterBtns.forEach(b => {
                    b.classList.remove('active', 'bg-indigo-600', 'text-white', 'shadow-md');
                    b.classList.add('bg-gray-100', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
                });
                this.classList.add('active', 'bg-indigo-600', 'text-white', 'shadow-md');
                this.classList.remove('bg-gray-100', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
                activeGroup = this.dataset.group;
                filterExtensions();
            });
        });
    }

    initBulkActions() {
        const bulkToolbar = document.getElementById('bulk-toolbar');
        const selectedCountEl = document.getElementById('selected-count');
        const selectAllBtn = document.getElementById('select-all-btn');
        const cancelSelectionBtn = document.getElementById('cancel-selection-btn');

        const getSelectedExtensions = () => {
            return Array.from(document.querySelectorAll('.extension-checkbox:checked')).map(cb => ({
                type: cb.dataset.type,
                uuid: cb.dataset.uuid
            }));
        };

        const updateBulkToolbar = () => {
            const selected = getSelectedExtensions();
            bulkToolbar.classList.toggle('hidden', selected.length === 0);
            selectedCountEl.textContent = selected.length;
        };

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('extension-checkbox')) {
                updateBulkToolbar();
            }
        });

        selectAllBtn?.addEventListener('click', () => {
            const checkboxes = document.querySelectorAll('.extension-checkbox:not(:checked)');
            if (checkboxes.length > 0) {
                checkboxes.forEach(cb => cb.checked = true);
            } else {
                document.querySelectorAll('.extension-checkbox').forEach(cb => cb.checked = false);
            }
            updateBulkToolbar();
        });

        cancelSelectionBtn?.addEventListener('click', () => {
            document.querySelectorAll('.extension-checkbox').forEach(cb => cb.checked = false);
            updateBulkToolbar();
        });

        const performBulkAction = async (action) => {
            const extensions = getSelectedExtensions();
            if (extensions.length === 0) return;

            const buttons = bulkToolbar.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            this.showToast(this.translations.processing, 'info');

            try {
                const response = await fetch(this.routes.bulk, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ extensions, action })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message || this.translations.success, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showToast(data.message || this.translations.error, 'error');
                }
            } catch (error) {
                this.showToast(this.translations.error, 'error');
            } finally {
                buttons.forEach(btn => btn.disabled = false);
            }
        };

        document.getElementById('bulk-install-btn')?.addEventListener('click', () => performBulkAction('install'));
        document.getElementById('bulk-enable-btn')?.addEventListener('click', () => performBulkAction('enable'));
        document.getElementById('bulk-disable-btn')?.addEventListener('click', () => performBulkAction('disable'));
    }

    initAjaxActions() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.ajax-action-btn');
            if (!btn) return;

            e.preventDefault();
            const action = btn.dataset.action;
            const type = btn.dataset.type;
            const uuid = btn.dataset.uuid;

            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>${this.translations.processing}</span>`;

            let url;
            if (action === 'enable') {
                url = this.routes.enable.replace('TYPE', type).replace('UUID', uuid);
            } else if (action === 'disable') {
                url = this.routes.disable.replace('TYPE', type).replace('UUID', uuid);
            } else {
                url = this.routes.update.replace('TYPE', type).replace('UUID', uuid);
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success !== false) {
                    this.showToast(data.message || data.success || this.translations.success, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showToast(data.error || data.message || this.translations.error, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (error) {
                this.showToast(this.translations.error, 'error');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
    }

    initModal() {
        const modal = document.getElementById('extension-modal');
        const modalContent = document.getElementById('modal-content');
        const modalBackdrop = document.getElementById('modal-backdrop');
        const modalClose = document.getElementById('modal-close');

        const openModal = (extensionData, itemElement) => {
            const data = typeof extensionData === 'string' ? JSON.parse(extensionData) : extensionData;
            const api = data.api || {};
            const tags = api.tags || [];
            const isInstalled = data.installed;
            const isEnabled = data.enabled;
            const isActivable = itemElement?.dataset.extensionActivable === '1';

            let actionBtn = '';
            if (isActivable) {
                if (isEnabled) {
                    actionBtn = `<button class="ajax-action-btn btn btn-danger flex items-center justify-center gap-2" data-action="disable" data-type="${data.type}s" data-uuid="${data.uuid}">
                        <i class="bi bi-ban"></i>${this.translations.disable}
                    </button>`;
                } else if (isInstalled) {
                    actionBtn = `<button class="ajax-action-btn btn btn-success flex items-center justify-center gap-2" data-action="enable" data-type="${data.type}s" data-uuid="${data.uuid}">
                        <i class="bi bi-check-circle"></i>${this.translations.enable}
                    </button>`;
                } else {
                    actionBtn = `<button class="ajax-action-btn btn btn-primary flex items-center justify-center gap-2" data-action="install" data-type="${data.type}s" data-uuid="${data.uuid}">
                        <i class="bi bi-cloud-download"></i>${this.translations.install}
                    </button>`;
                }
            } else if (api.route) {
                actionBtn = `<a href="${api.route}" target="_blank" class="btn btn-primary flex items-center justify-center gap-2">
                    <i class="bi bi-cart"></i>${this.translations.buyNow}
                </a>`;
            }

            const tagsHtml = tags.map(tag => `
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300">
                    <i class="${tag.icon} mr-1"></i>${tag.name}
                </span>
            `).join('');

            modalContent.innerHTML = `
                <div class="p-6">
                    <div class="flex items-start gap-5 mb-6 pb-6 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-xl flex items-center justify-center overflow-hidden">
                            <img src="${api.thumbnail || 'https://via.placeholder.com/150'}" class="max-h-full max-w-full object-contain" alt="${api.name || data.uuid}">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">${api.name || data.uuid}</h2>
                            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-2">
                                <span>${this.translations.author}: <strong>${api.author?.name || 'Unknown'}</strong></span>
                                ${data.version ? `<span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-700 rounded font-mono text-xs">${data.version}</span>` : ''}
                            </div>
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                ${api.formatted_price || 'Free'}
                            </div>
                        </div>
                    </div>

                    <div class="prose prose-sm dark:prose-invert max-w-none mb-6 text-gray-600 dark:text-gray-300 leading-relaxed modal-description">
                        ${marked.parse(api.description || api.short_description || '')}
                    </div>

                    ${tags.length > 0 ? `
                    <div class="mb-6">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">${this.translations.tags}</div>
                        <div class="flex flex-wrap gap-2">${tagsHtml}</div>
                    </div>
                    ` : ''}

                    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        ${actionBtn}
                        ${api.route ? `<a href="${api.route}" target="_blank" class="btn btn-secondary flex items-center justify-center gap-2">
                            <i class="bi bi-box-arrow-up-right"></i>${this.translations.viewDetails}
                        </a>` : ''}
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.extension-detail-trigger');
            if (trigger) {
                const card = trigger.closest('.extension-item');
                if (card?.dataset.extensionData) {
                    openModal(card.dataset.extensionData, card);
                }
            }
        });

        modalClose?.addEventListener('click', closeModal);
        modalBackdrop?.addEventListener('click', closeModal);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }
}

window.ExtensionManager = ExtensionManager;
