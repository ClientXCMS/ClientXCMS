<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Bottom menu management with inline editing and auto-save.
 *
 * Year: 2026
 */
?>

@extends('admin.settings.sidebar')
@section('title', __('personalization.bottom_menu.title'))

@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.bottom_menu.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('personalization.bottom_menu.description') ?? 'Gerez les liens affiches en bas du footer.' }}
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary text-sm" id="saveAllButton" disabled>
                    <i class="bi bi-check-lg mr-1"></i>Auto-save
                </button>
                <button type="button" class="btn btn-secondary text-sm" onclick="addMenuItem()">
                    <i class="bi bi-plus-lg mr-1"></i>{{ __('personalization.addelement') }}
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert text-red-700 bg-red-100 mt-2" role="alert">
                @foreach ($errors->all() as $error)
                    {!! $error !!}<br/>
                @endforeach
            </div>
        @endif

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
            <p class="text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2">
                <i class="bi bi-info-circle mt-0.5"></i>
                <span>{{ __('personalization.bottom_menu.help') ?? 'Modifiez directement les liens du menu de bas de page. Les modifications sont sauvegardees automatiquement.' }}</span>
            </p>
        </div>

        <div id="menu-sections-container" class="space-y-3">
            @foreach ($menus as $menu)
            @php
                $menuName = $menu->trans('name', $menu->name);
                $menuUrl = $menu->trans('url', $menu->url);
            @endphp
            <div class="menu-item p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center gap-2" data-id="{{ $menu->id }}">
                <span class="item-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="bi bi-grip-vertical"></i>
                </span>
                <span class="item-number inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold">{{ $loop->iteration }}</span>
                <input type="text" value="{{ $menu->icon }}"
                       class="item-icon input-text text-sm py-1.5 w-28"
                       placeholder="bi bi-link"
                       onchange="markChanged(this)">
                <input type="text" value="{{ $menuName }}"
                       class="item-name input-text text-sm py-1.5 w-40 min-w-[10rem]"
                       placeholder="{{ __('global.name') }}"
                       onchange="markChanged(this)">
                <button type="button" class="btn-translate p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded" onclick="openTranslation({{ $menu->id }})" title="Traductions">
                    <i class="bi bi-translate"></i>
                </button>
                <input type="text" value="{{ $menuUrl }}"
                       class="item-url input-text text-sm py-1.5 w-48"
                       placeholder="/url"
                       onchange="markChanged(this)">
                <select class="item-linktype input-text text-xs py-1.5 w-24" onchange="markChanged(this)">
                    <option value="link" {{ $menu->link_type == 'link' ? 'selected' : '' }}>Link</option>
                    <option value="new_tab" {{ $menu->link_type == 'new_tab' ? 'selected' : '' }}>New tab</option>
                </select>
                <div class="flex items-center gap-1 ml-auto">
                    <button type="button" onclick="moveMenuItem(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Monter">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" onclick="moveMenuItem(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Descendre">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" onclick="deleteMenuItem(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            <button type="button" onclick="addMenuItem()"
                    class="w-full py-2.5 px-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:border-primary dark:hover:text-primary transition-colors flex items-center justify-center gap-2">
                <i class="bi bi-plus-circle"></i>
                {{ __('personalization.addelement') }}
            </button>
        </div>
    </div>

    {{-- Footer Description Settings --}}
    <div class="card mt-4">
        <div class="card-heading">
            <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                {{ __('personalization.theme.fields.theme_footer_description') }}
            </h4>
        </div>
        <form action="{{ route('admin.personalization.bottom_menu') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin/shared/textarea', ['name' => 'theme_footer_description', 'label' => __('personalization.theme.fields.theme_footer_description'), 'value' => nl2br(setting('theme_footer_description')), 'help' => __('personalization.theme.fields.theme_footer_description_help'), 'translatable' => setting_is_saved('theme_footer_description')])
            <div class="mt-3">
                @include('admin/shared/textarea', ['name' => 'theme_footer_topheberg', 'label' => __('personalization.theme.fields.theme_footer_topheberg'), 'value' => setting('theme_footer_topheberg')])
            </div>
            <button type="submit" class="btn btn-primary mt-3">{{ __('global.save') }}</button>
        </form>
    </div>

    {{-- Translation overlays for menu items --}}
    @php($locales = \App\Services\Core\LocaleService::getLocales(true, true))
    @foreach ($menus as $menu)
    <div id="translations-overlay-menu-{{ $menu->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $menu->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $menu->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.index') }}" class="p-4">
            <input type="hidden" name="model" value="{{ get_class($menu) }}">
            <input type="hidden" name="model_id" value="{{ $menu->id }}">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-4 mb-2">{{ $locale['name'] }}</h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('global.name') }}</label>
                <input type="text" name="translations[{{ $_key }}][name]" value="{{ $menu->trans('name', '', $_key) }}" class="input-text w-full">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @endforeach
    @include('admin/translations/settings-overlay', ['keys' => ['theme_footer_description' => 'textarea'], 'class' => \App\Models\Admin\Setting::class, 'id' => 0])

<script>
(function() {
    const container = document.getElementById('menu-sections-container');
    const saveButton = document.getElementById('saveAllButton');
    const type = 'bottom';
    const csrfToken = '{{ csrf_token() }}';
    let hasChanges = false;
    let newItemCounter = 0;
    let saveTimeout = null;
    let isSaving = false;

    // Trigger auto-save with debounce
    function triggerAutoSave(immediate = false) {
        if (saveTimeout) clearTimeout(saveTimeout);
        if (immediate) {
            saveAll();
        } else {
            saveTimeout = setTimeout(() => saveAll(), 500);
        }
    }

    // Mark form as changed
    window.markChanged = function(element) {
        hasChanges = true;
        saveButton.disabled = false;
        element.closest('.menu-item').classList.add('has-changes');
        triggerAutoSave();
    };

    // Update item numbers and button states
    function updateUI() {
        const items = container.querySelectorAll('.menu-item');
        items.forEach((item, index) => {
            item.querySelector('.item-number').textContent = index + 1;
            item.querySelector('.btn-move-up').disabled = index === 0;
            item.querySelector('.btn-move-down').disabled = index === items.length - 1;
        });
    }

    // Add new menu item
    window.addMenuItem = function() {
        newItemCounter++;
        const tempId = 'new-' + newItemCounter;
        const index = container.querySelectorAll('.menu-item').length + 1;

        const html = `
            <div class="menu-item p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 border-primary flex items-center gap-2 has-changes" data-id="${tempId}" data-new="true">
                <span class="item-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="bi bi-grip-vertical"></i>
                </span>
                <span class="item-number inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold">${index}</span>
                <input type="text" value="bi bi-link"
                       class="item-icon input-text text-sm py-1.5 w-28"
                       placeholder="bi bi-link"
                       onchange="markChanged(this)">
                <input type="text" value=""
                       class="item-name input-text text-sm py-1.5 w-40 min-w-[10rem]"
                       placeholder="{{ __('global.name') }}"
                       onchange="markChanged(this)">
                <input type="text" value=""
                       class="item-url input-text text-sm py-1.5 w-48"
                       placeholder="/url"
                       onchange="markChanged(this)">
                <select class="item-linktype input-text text-xs py-1.5 w-24" onchange="markChanged(this)">
                    <option value="link" selected>Link</option>
                    <option value="new_tab">New tab</option>
                </select>
                <div class="flex items-center gap-1 ml-auto">
                    <button type="button" onclick="moveMenuItem(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Monter">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" onclick="moveMenuItem(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Descendre">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" onclick="deleteMenuItem(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        hasChanges = true;
        saveButton.disabled = false;
        updateUI();

        // Focus on the name field
        container.lastElementChild.querySelector('.item-name').focus();
        triggerAutoSave(true);
    };

    // Delete menu item
    window.deleteMenuItem = function(button) {
        const item = button.closest('.menu-item');
        const itemId = item.dataset.id;

        // Delete via API if not new
        if (!item.dataset.new) {
            fetch('/admin/menulink/' + itemId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
        }

        item.remove();
        updateUI();
        triggerAutoSave(true);
    };

    // Move item up/down
    window.moveMenuItem = function(button, direction) {
        const item = button.closest('.menu-item');
        const items = Array.from(container.querySelectorAll('.menu-item'));
        const index = items.indexOf(item);
        const newIndex = index + direction;

        if (newIndex < 0 || newIndex >= items.length) return;

        if (direction === -1) {
            container.insertBefore(item, items[newIndex]);
        } else {
            container.insertBefore(items[newIndex], item);
        }

        hasChanges = true;
        saveButton.disabled = false;
        updateUI();
        triggerAutoSave(true);
    };

    // Open translation overlay
    window.openTranslation = function(id) {
        const overlay = document.getElementById('translations-overlay-menu-' + id);
        if (overlay && window.HSOverlay) {
            window.HSOverlay.open(overlay);
        } else if (overlay) {
            overlay.classList.remove('hidden');
            overlay.classList.add('hs-overlay-open');
        }
    };

    // Helper function for AJAX requests
    async function ajaxRequest(url, method, data = null) {
        const options = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        if (data) options.body = JSON.stringify(data);

        try {
            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                console.error('[AJAX ERROR]', result);
                return { success: false, error: result };
            }

            return { success: true, data: result };
        } catch (error) {
            console.error('[AJAX EXCEPTION]', error);
            return { success: false, error: error.message };
        }
    }

    // Save all changes (auto-save)
    async function saveAll() {
        if (isSaving || !hasChanges) return;
        isSaving = true;

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i>Sauvegarde...';

        const items = container.querySelectorAll('.menu-item');
        const order = [];
        let hasError = false;

        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            const isNew = item.dataset.new;
            let itemId = item.dataset.id;

            const itemData = {
                name: item.querySelector('.item-name').value,
                url: item.querySelector('.item-url').value || '#',
                icon: item.querySelector('.item-icon').value,
                link_type: item.querySelector('.item-linktype').value,
                allowed_role: 'all',
                type: type,
                position: i + 1
            };

            if (isNew) {
                const result = await ajaxRequest('/admin/menulink/' + type, 'POST', itemData);
                if (result.success && result.data.id) {
                    itemId = result.data.id;
                    item.dataset.id = itemId;
                    delete item.dataset.new;
                } else {
                    hasError = true;
                    console.error('[SAVE] Creation failed:', result.error);
                }
            } else if (item.classList.contains('has-changes')) {
                const result = await ajaxRequest('/admin/menulink/' + itemId, 'PUT', itemData);
                if (!result.success) {
                    hasError = true;
                    console.error('[SAVE] Update failed for item', itemId);
                }
            }

            order.push({ id: itemId, children: [] });
        }

        // Save order only if no errors
        if (!hasError) {
            await ajaxRequest('/admin/menulink/' + type + '/sort', 'POST', {
                items: order.map(o => ({ id: o.id, children: [] }))
            });
        }

        // Clear change markers
        container.querySelectorAll('.has-changes').forEach(el => el.classList.remove('has-changes'));
        hasChanges = false;
        isSaving = false;

        saveButton.innerHTML = '<i class="bi bi-check-lg mr-1"></i>Auto-save';
        saveButton.disabled = true;

        if (hasError) {
            saveButton.innerHTML = '<i class="bi bi-exclamation-triangle mr-1"></i>Erreur';
            setTimeout(() => {
                saveButton.innerHTML = '<i class="bi bi-check-lg mr-1"></i>Auto-save';
            }, 2000);
        }
    }

    // Manual save button click
    saveButton.addEventListener('click', () => saveAll());

    // Initial UI update
    updateUI();
})();
</script>
@endsection
