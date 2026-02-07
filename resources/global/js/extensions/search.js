/**
 * Extensions Universal Search (Story 2.2)
 *
 * Debounced cross-tab filtering, tab counter updates,
 * keyboard shortcuts, group filtering, context-aware default tab.
 */

import { getTranslation, debounce } from './utils.js';

const DEBOUNCE_MS = 200;
const TAB_IDS = ['installed', 'discover', 'themes'];

const ACTIVE_TAB_CLASSES = [
    'active', 'bg-white', 'dark:bg-slate-700',
    'text-indigo-600', 'dark:text-indigo-400', 'shadow-sm',
];
const INACTIVE_TAB_CLASSES = ['text-gray-600', 'dark:text-gray-400'];

const ACTIVE_BADGE_CLASSES = [
    'bg-indigo-100', 'dark:bg-indigo-900/50',
    'text-indigo-600', 'dark:text-indigo-400',
];
const INACTIVE_BADGE_CLASSES = [
    'bg-gray-200', 'dark:bg-slate-600',
    'text-gray-600', 'dark:text-gray-400',
];

let activeGroup = 'all';
let searchQuery = '';
let currentTab = 'installed';

function getCardsInTab(tabId) {
    const tab = document.getElementById(`tab-${tabId}`);
    return tab ? tab.querySelectorAll('.js-extension-card') : [];
}

function matchesSearch(card, query) {
    if (!query) return true;
    const q = query.toLowerCase();
    const name = (card.dataset.name || '').toLowerCase();
    const description = (card.dataset.description || '').toLowerCase();
    const author = (card.dataset.author || '').toLowerCase();
    return name.includes(q) || description.includes(q) || author.includes(q);
}

function matchesGroup(card, group) {
    if (group === 'all') return true;
    return card.dataset.category === group;
}

/**
 * Filters cards across all tabs and updates counters + UI.
 */
export function filterAndCount() {
    const counts = {};

    TAB_IDS.forEach(tabId => {
        const cards = getCardsInTab(tabId);
        let count = 0;

        cards.forEach(card => {
            const groupFilter = tabId === 'discover' ? activeGroup : 'all';
            const visible = matchesSearch(card, searchQuery)
                && matchesGroup(card, groupFilter);

            card.classList.toggle('hidden', !visible);
            card.style.animation = visible ? 'fadeIn 0.3s ease forwards' : '';
            if (visible) count++;
        });

        counts[tabId] = count;
    });

    updateTabCounters(counts);
    updateNoResults(counts);
    updateFeaturedSections();
    updateExtensionsCount(counts);
    announceResults(counts);
}

function updateTabCounters(counts) {
    document.querySelectorAll('.main-tab-btn').forEach(btn => {
        const tabId = btn.dataset.tab;
        const counter = btn.querySelector('.js-tab-counter');
        if (counter && counts[tabId] !== undefined) {
            counter.textContent = counts[tabId];
        }
    });
}

/**
 * Shows/hides the per-tab no-results message.
 */
function updateNoResults(counts) {
    TAB_IDS.forEach(tabId => {
        const tabEl = document.getElementById(`tab-${tabId}`);
        if (!tabEl) return;

        const noResults = tabEl.querySelector('.js-no-results');
        const grids = tabEl.querySelectorAll('[id$="-grid"]');
        if (!noResults) return;

        const isEmpty = counts[tabId] === 0 && !!searchQuery;
        noResults.classList.toggle('hidden', !isEmpty);
        grids.forEach(grid => grid.classList.toggle('hidden', isEmpty));
    });
}

function updateFeaturedSections() {
    const hide = !!(searchQuery || activeGroup !== 'all');
    document.querySelectorAll('#section-new, #section-popular').forEach(section => {
        section.classList.toggle('hidden', hide);
    });
}

function updateExtensionsCount(counts) {
    const countEl = document.getElementById('extensions-count');
    if (!countEl) return;

    const total = counts['discover'] || 0;
    countEl.textContent = `${total} extension${total !== 1 ? 's' : ''}`;
}

function announceResults(counts) {
    const announcer = document.getElementById('js-search-announcer');
    if (!announcer || !searchQuery) return;

    const totalVisible = Object.values(counts).reduce((a, b) => a + b, 0);
    announcer.textContent = `${totalVisible} ${getTranslation('results_found', 'results found')}`;
}

/**
 * Toggles active/inactive styling on a tab button.
 */
function setTabActive(btn, isActive) {
    const addClasses = isActive ? ACTIVE_TAB_CLASSES : INACTIVE_TAB_CLASSES;
    const removeClasses = isActive ? INACTIVE_TAB_CLASSES : ACTIVE_TAB_CLASSES;

    btn.classList.add(...addClasses);
    btn.classList.remove(...removeClasses);

    const badge = btn.querySelector('.js-tab-counter')?.parentElement;
    if (!badge) return;

    const badgeAdd = isActive ? ACTIVE_BADGE_CLASSES : INACTIVE_BADGE_CLASSES;
    const badgeRemove = isActive ? INACTIVE_BADGE_CLASSES : ACTIVE_BADGE_CLASSES;
    badge.classList.add(...badgeAdd);
    badge.classList.remove(...badgeRemove);
}

/**
 * Switches to the given tab and re-applies filters.
 * @param {string} tabId
 */
export function switchTab(tabId) {
    currentTab = tabId;

    document.querySelectorAll('.main-tab-btn').forEach(btn => {
        setTabActive(btn, btn.dataset.tab === tabId);
    });

    document.querySelectorAll('.tab-content').forEach(content => {
        const isTarget = content.id === `tab-${tabId}`;
        content.classList.toggle('hidden', !isTarget);
        if (isTarget) {
            content.style.animation = 'fadeIn 0.3s ease forwards';
        }
    });

    filterAndCount();
}

/**
 * Shows/hides the clear button based on search input value.
 */
function updateClearButton(searchInput) {
    const clearBtn = document.getElementById('js-search-clear');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', !searchInput.value.trim());
    }
}

/**
 * Initializes the search module: binds events, sets default tab.
 */
export function init() {
    const searchInput = document.getElementById('extension-search');
    const clearBtn = document.getElementById('js-search-clear');

    // Contextual default tab: discover if no installed extensions
    if (getCardsInTab('installed').length === 0) {
        switchTab('discover');
    }

    if (searchInput) {
        const debouncedSearch = debounce(() => {
            searchQuery = searchInput.value.trim();
            filterAndCount();
            updateClearButton(searchInput);
        }, DEBOUNCE_MS);

        searchInput.addEventListener('input', debouncedSearch);

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                this.value = '';
                searchQuery = '';
                filterAndCount();
                updateClearButton(this);
                this.blur();
            }
        });
    }

    // Clear button
    if (clearBtn && searchInput) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchQuery = '';
            filterAndCount();
            updateClearButton(searchInput);
            searchInput.focus();
        });
    }

    // Keyboard shortcut '/' to focus search (desktop)
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
            const target = e.target;
            if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) return;
            e.preventDefault();
            searchInput?.focus();
        }
    });

    // Tab switching
    document.querySelectorAll('.main-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchTab(this.dataset.tab);
        });
    });

    // Group filter buttons
    const groupFilterBtns = document.querySelectorAll('.group-filter-btn');
    groupFilterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            groupFilterBtns.forEach(b => {
                b.classList.remove('active', 'bg-indigo-600', 'text-white', 'shadow-md');
                b.classList.add('bg-gray-100', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
            });
            this.classList.add('active', 'bg-indigo-600', 'text-white', 'shadow-md');
            this.classList.remove('bg-gray-100', 'dark:bg-slate-700', 'text-gray-700', 'dark:text-gray-300');
            activeGroup = this.dataset.group;
            filterAndCount();
        });
    });

    filterAndCount();

    // Backward compat for external access
    window.ExtensionSearch = { switchTab, filterAndCount };
}
