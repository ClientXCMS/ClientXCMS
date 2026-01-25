import Sortable from 'sortablejs';

/**
 * Menu Inline Editor
 * Handles inline editing of menu items with 3-level hierarchy support.
 * Features: CRUD operations, drag-and-drop, validation, bulk save.
 */
window.menuInlineEditor = function(config) {
    return {
        items: config.items || [],
        type: config.type,
        saveUrl: config.saveUrl,
        csrfToken: config.csrfToken,
        roles: config.roles || {},
        linkTypes: config.linkTypes || {},
        locales: config.locales || {},
        supportDropdown: config.supportDropdown || false,

        isSaving: false,
        hasChanges: false,
        errors: {},
        sortableInstances: [],

        // Counter for generating temporary IDs for new items
        tempIdCounter: 0,

        init() {
            this.$watch('items', () => {
                this.hasChanges = true;
            }, { deep: true });

            this.$nextTick(() => {
                this.initSortable();
            });
        },

        /**
         * Initialize Sortable.js for drag-and-drop at all levels.
         */
        initSortable() {
            this.destroySortable();

            const lists = this.$el.querySelectorAll('[data-sortable]');
            lists.forEach((list) => {
                const depth = parseInt(list.dataset.depth || 0);

                const instance = Sortable.create(list, {
                    animation: 150,
                    handle: '.drag-handle',
                    group: {
                        name: 'menu-items',
                        put: (to) => {
                            const targetDepth = parseInt(to.el.dataset.depth || 0);
                            return targetDepth < 3;
                        }
                    },
                    onEnd: (evt) => {
                        this.handleDragEnd(evt);
                    }
                });

                this.sortableInstances.push(instance);
            });
        },

        /**
         * Destroy all Sortable instances before reinitializing.
         */
        destroySortable() {
            this.sortableInstances.forEach(instance => instance.destroy());
            this.sortableInstances = [];
        },

        /**
         * Handle drag end event - sync DOM order with Alpine state.
         */
        handleDragEnd(evt) {
            const fromList = evt.from;
            const toList = evt.to;
            const itemId = evt.item.dataset.id;

            const fromPath = fromList.dataset.path || '';
            const toPath = toList.dataset.path || '';

            const fromItems = this.getItemsByPath(fromPath);
            const toItems = this.getItemsByPath(toPath);

            const oldIndex = evt.oldIndex;
            const newIndex = evt.newIndex;

            if (fromPath === toPath) {
                // Reorder within same parent
                const [moved] = fromItems.splice(oldIndex, 1);
                fromItems.splice(newIndex, 0, moved);
            } else {
                // Move between parents
                const [moved] = fromItems.splice(oldIndex, 1);
                toItems.splice(newIndex, 0, moved);
            }

            this.hasChanges = true;
            this.$nextTick(() => this.initSortable());
        },

        /**
         * Get items array by dot-notation path.
         * Examples: '' -> this.items, '0.children' -> this.items[0].children
         */
        getItemsByPath(path) {
            if (!path) return this.items;

            const parts = path.split('.');
            let current = this.items;

            for (const part of parts) {
                if (part === 'children') {
                    current = current.children || [];
                } else {
                    const index = parseInt(part);
                    current = current[index];
                }
            }

            return current.children || current;
        },

        /**
         * Generate a temporary ID for new items.
         */
        generateTempId() {
            this.tempIdCounter++;
            return `temp_${this.tempIdCounter}`;
        },

        /**
         * Create a new empty menu item.
         */
        createEmptyItem(depth) {
            return {
                _tempId: this.generateTempId(),
                id: null,
                name: '',
                url: '',
                icon: '',
                badge: '',
                description: '',
                link_type: depth === 0 && this.supportDropdown ? 'dropdown' : 'link',
                allowed_role: 'all',
                isNew: true,
                isDeleted: false,
                children: [],
                translations: {}
            };
        },

        /**
         * Add a new item at the specified depth.
         * @param {Object|null} parent Parent item or null for root level
         * @param {number} depth Current depth (0-based)
         */
        addItem(parent = null, depth = 0) {
            if (depth >= 3) return;

            const newItem = this.createEmptyItem(depth);

            if (parent === null) {
                this.items.push(newItem);
            } else {
                if (!parent.children) {
                    parent.children = [];
                }
                parent.children.push(newItem);

                // If parent now has children, ensure it's a dropdown
                if (depth === 0 && parent.children.length > 0 && this.supportDropdown) {
                    parent.link_type = 'dropdown';
                }
            }

            this.hasChanges = true;
            this.$nextTick(() => this.initSortable());
        },

        /**
         * Delete an item (soft delete for existing, hard delete for new).
         */
        deleteItem(items, index) {
            const item = items[index];

            if (item.isNew || !item.id) {
                // Hard delete for new items
                items.splice(index, 1);
            } else {
                // Soft delete for existing items
                item.isDeleted = true;
            }

            this.hasChanges = true;
            this.$nextTick(() => this.initSortable());
        },

        /**
         * Restore a soft-deleted item.
         */
        restoreItem(item) {
            item.isDeleted = false;
            this.hasChanges = true;
        },

        /**
         * Validate a single item.
         * @returns {boolean} True if valid
         */
        validateItem(item, path = '') {
            let isValid = true;
            const itemPath = path || item._tempId || item.id;

            // Required: name
            if (!item.name || item.name.trim() === '') {
                this.errors[`${itemPath}.name`] = true;
                isValid = false;
            } else {
                delete this.errors[`${itemPath}.name`];
            }

            // Required: url
            if (!item.url || item.url.trim() === '') {
                this.errors[`${itemPath}.url`] = true;
                isValid = false;
            } else {
                delete this.errors[`${itemPath}.url`];
            }

            // Validate children recursively
            if (item.children && item.children.length > 0) {
                item.children.forEach((child, idx) => {
                    if (!child.isDeleted) {
                        const childValid = this.validateItem(child, `${itemPath}.children.${idx}`);
                        if (!childValid) isValid = false;
                    }
                });
            }

            return isValid;
        },

        /**
         * Validate all items before save.
         * @returns {boolean} True if all items are valid
         */
        validateAll() {
            this.errors = {};
            let isValid = true;

            this.items.forEach((item, idx) => {
                if (!item.isDeleted) {
                    const itemValid = this.validateItem(item, `item_${idx}`);
                    if (!itemValid) isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Check if a field has an error.
         */
        hasError(item, field, index = null, parentIndex = null) {
            const itemId = item._tempId || item.id;
            const basePath = parentIndex !== null ? `item_${parentIndex}.children.${index}` : `item_${index}`;
            return this.errors[`${basePath}.${field}`] === true;
        },

        /**
         * Serialize items for API submission.
         * Filters out deleted items and prepares data structure.
         */
        serializeItems(items = null) {
            const source = items || this.items;

            return source
                .filter(item => !item.isDeleted || item.id)
                .map(item => ({
                    id: item.id || null,
                    name: item.name,
                    url: item.url,
                    icon: item.icon || null,
                    badge: item.badge || null,
                    description: item.description || null,
                    link_type: item.link_type,
                    allowed_role: item.allowed_role,
                    isDeleted: item.isDeleted || false,
                    translations: item.translations || {},
                    children: item.children && item.children.length > 0
                        ? this.serializeItems(item.children)
                        : []
                }));
        },

        /**
         * Save all items via bulk API.
         */
        async save() {
            if (!this.validateAll()) {
                this.showNotification('error', window.menuEditorTranslations?.validationError || 'Please fix validation errors before saving.');
                return;
            }

            this.isSaving = true;

            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        items: this.serializeItems()
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.hasChanges = false;
                    this.showNotification('success', data.message || window.menuEditorTranslations?.saved || 'Menu saved successfully.');
                    // Reload to get fresh data with proper IDs
                    window.location.reload();
                } else {
                    this.showNotification('error', data.error || window.menuEditorTranslations?.saveError || 'An error occurred while saving.');
                }
            } catch (error) {
                console.error('Save error:', error);
                this.showNotification('error', window.menuEditorTranslations?.saveError || 'An error occurred while saving.');
            } finally {
                this.isSaving = false;
            }
        },

        /**
         * Show notification toast.
         */
        showNotification(type, message) {
            // Use existing toast system if available
            if (window.Toastify) {
                window.Toastify({
                    text: message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: type === 'success' ? '#10B981' : '#EF4444'
                }).showToast();
            } else {
                alert(message);
            }
        },

        /**
         * Open translation modal for an item.
         */
        openTranslationModal(item) {
            // Dispatch custom event to open modal
            this.$dispatch('open-translation-modal', {
                item: item,
                itemId: item.id || item._tempId
            });
        },

        /**
         * Update translations for an item.
         */
        updateTranslations(itemId, translations) {
            const item = this.findItemById(itemId);
            if (item) {
                item.translations = translations;
                this.hasChanges = true;
            }
        },

        /**
         * Find item by ID or temp ID recursively.
         */
        findItemById(id, items = null) {
            const source = items || this.items;

            for (const item of source) {
                if (item.id === id || item._tempId === id) {
                    return item;
                }
                if (item.children && item.children.length > 0) {
                    const found = this.findItemById(id, item.children);
                    if (found) return found;
                }
            }

            return null;
        },

        /**
         * Get visible items (non-deleted).
         */
        getVisibleItems(items = null) {
            const source = items || this.items;
            return source.filter(item => !item.isDeleted);
        },

        /**
         * Check if can add children (depth < 3).
         */
        canAddChildren(depth) {
            return depth < 2;
        },

        /**
         * Get link type options based on depth and dropdown support.
         */
        getLinkTypes(depth) {
            if (depth === 0 && this.supportDropdown) {
                return this.linkTypes;
            }
            // Remove dropdown option for child items
            const types = { ...this.linkTypes };
            delete types.dropdown;
            return types;
        }
    };
};
