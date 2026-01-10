<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Footer columns management with parent/child (sections/links) support.
 *
 * Year: 2026
 */
?>

@extends('admin.settings.sidebar')
@section('title', __('personalization.footer_menu.title'))

@section('setting')
    @if (!$supported)
        <div class="alert text-yellow-700 bg-yellow-100 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle mr-2"></i>
            {{ __('personalization.footer_menu.not_supported') }}
        </div>
    @endif

    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.footer_menu.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('personalization.footer_menu.description') }}
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary text-sm" id="saveAllButton" disabled>
                    <i class="bi bi-check-lg mr-1"></i>{{ __('global.save') }}
                </button>
                <button type="button" class="btn btn-secondary text-sm" onclick="addSection()">
                    <i class="bi bi-plus-lg mr-1"></i>{{ __('personalization.footer_menu.add_section') }}
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
                <span>{{ __('personalization.footer_menu.help') }}</span>
            </p>
        </div>

        <div id="sections-container" class="space-y-4">
            @foreach ($menus as $section)
            @php
                $sectionName = $section->trans('name', $section->name);
            @endphp
            <div class="section-item bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4" data-id="{{ $section->id }}">
                {{-- Section Header --}}
                <div class="flex items-center gap-3 mb-3">
                    <span class="section-number inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary text-sm font-bold">{{ $loop->iteration }}</span>
                    <input type="text" value="{{ $section->name ?: $section->trans('name') }}"
                           class="section-name input-text text-sm py-1.5 w-48 font-semibold"
                           placeholder="{{ __('personalization.footer_menu.section_name') }}"
                           data-field="name"
                           onchange="markChanged(this)">
                    <button type="button" class="btn-translate p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded" onclick="openTranslation({{ $section->id }})" title="Traductions">
                        <i class="bi bi-translate"></i>
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="moveSection(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="{{ __('global.move_up') ?? 'Monter' }}">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" onclick="moveSection(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="{{ __('global.move_down') ?? 'Descendre' }}">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" onclick="deleteSection(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                {{-- Section Links --}}
                <div class="links-container ml-10 space-y-2">
                    @foreach ($section->children()->orderBy('position')->get() as $link)
                    @php
                        $linkName = $link->trans('name', $link->name);
                        $linkUrl = $link->trans('url', $link->url);
                    @endphp
                    <div class="link-item p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center gap-2" data-id="{{ $link->id }}">
                        <span class="link-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                        <input type="text" value="{{ $link->name ?: $link->trans('name') }}"
                               class="link-name input-text text-sm py-1 w-36 min-w-[9rem]"
                               placeholder="{{ __('global.name') }}"
                               data-field="name"
                               onchange="markChanged(this)">
                        <button type="button" class="btn-translate p-1 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded text-xs" onclick="openTranslation({{ $link->id }})" title="Traductions">
                            <i class="bi bi-translate"></i>
                        </button>
                        <input type="text" value="{{ $linkUrl }}"
                               class="link-url input-text text-sm py-1 w-44"
                               placeholder="/url"
                               data-field="url"
                               onchange="markChanged(this)">
                        <select class="link-linktype input-text text-xs py-1 w-20" data-field="link_type" onchange="markChanged(this)">
                            <option value="link" {{ $link->link_type == 'link' ? 'selected' : '' }}>Link</option>
                            <option value="new_tab" {{ $link->link_type == 'new_tab' ? 'selected' : '' }}>New tab</option>
                        </select>
                        <button type="button" onclick="deleteLink(this)" class="btn-delete p-1 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                    @endforeach
                </div>

                {{-- Add Link Button --}}
                <div class="ml-10 mt-2">
                    <button type="button" onclick="addLink(this)"
                            class="w-full py-1.5 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs font-medium text-gray-500 dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:border-primary dark:hover:text-primary transition-colors flex items-center justify-center gap-1">
                        <i class="bi bi-plus"></i>
                        {{ __('personalization.footer_menu.add_link') }}
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            <button type="button" onclick="addSection()"
                    class="w-full py-2.5 px-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:border-primary dark:hover:text-primary transition-colors flex items-center justify-center gap-2">
                <i class="bi bi-plus-circle"></i>
                {{ __('personalization.footer_menu.add_section') }}
            </button>
        </div>
    </div>

    {{-- Translation overlays for all menu items --}}
    @php($locales = \App\Services\Core\LocaleService::getLocales(true, true))
    @foreach ($menus as $section)
    <div id="translations-overlay-menu-{{ $section->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $section->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $section->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.index') }}" class="p-4">
            <input type="hidden" name="model" value="{{ get_class($section) }}">
            <input type="hidden" name="model_id" value="{{ $section->id }}">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-4 mb-2">{{ $locale['name'] }}</h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('global.name') }}</label>
                <input type="text" name="translations[{{ $_key }}][name]" value="{{ $section->trans('name', '', $_key) }}" class="input-text w-full">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @foreach ($section->children as $link)
    <div id="translations-overlay-menu-{{ $link->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $link->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $link->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.index') }}" class="p-4">
            <input type="hidden" name="model" value="{{ get_class($link) }}">
            <input type="hidden" name="model_id" value="{{ $link->id }}">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-4 mb-2">{{ $locale['name'] }}</h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('global.name') }}</label>
                <input type="text" name="translations[{{ $_key }}][name]" value="{{ $link->trans('name', '', $_key) }}" class="input-text w-full">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @endforeach
    @endforeach

<script>
(function() {
    const container = document.getElementById('sections-container');
    const saveButton = document.getElementById('saveAllButton');
    const type = 'footer';
    const csrfToken = '{{ csrf_token() }}';
    let hasChanges = false;
    let newSectionCounter = 0;
    let newLinkCounter = 0;
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
        const item = element.closest('.section-item') || element.closest('.link-item');
        if (item) item.classList.add('has-changes');
        triggerAutoSave();
    };

    // Update section numbers and button states
    function updateSectionUI() {
        const sections = container.querySelectorAll('.section-item');
        sections.forEach((section, index) => {
            section.querySelector('.section-number').textContent = index + 1;
            section.querySelector('.btn-move-up').disabled = index === 0;
            section.querySelector('.btn-move-down').disabled = index === sections.length - 1;
        });
    }

    // Add new section (column)
    window.addSection = function() {
        newSectionCounter++;
        const tempId = 'new-section-' + newSectionCounter;
        const index = container.querySelectorAll('.section-item').length + 1;

        const html = `
            <div class="section-item bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 border-primary p-4 has-changes" data-id="${tempId}" data-new="true">
                <div class="flex items-center gap-3 mb-3">
                    <span class="section-number inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary text-sm font-bold">${index}</span>
                    <input type="text" value=""
                           class="section-name input-text text-sm py-1.5 w-48 font-semibold"
                           placeholder="{{ __('personalization.footer_menu.section_name') }}"
                           data-field="name"
                           onchange="markChanged(this)">
                    <div class="flex-1"></div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="moveSection(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" onclick="moveSection(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" onclick="deleteSection(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="links-container ml-10 space-y-2"></div>
                <div class="ml-10 mt-2">
                    <button type="button" onclick="addLink(this)"
                            class="w-full py-1.5 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs font-medium text-gray-500 dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:border-primary dark:hover:text-primary transition-colors flex items-center justify-center gap-1">
                        <i class="bi bi-plus"></i>
                        {{ __('personalization.footer_menu.add_link') }}
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        hasChanges = true;
        saveButton.disabled = false;
        updateSectionUI();

        container.lastElementChild.querySelector('.section-name').focus();
        triggerAutoSave(true);
    };

    // Add new link to section
    window.addLink = function(button) {
        newLinkCounter++;
        const tempId = 'new-link-' + newLinkCounter;
        const section = button.closest('.section-item');
        const linksContainer = section.querySelector('.links-container');

        const html = `
            <div class="link-item p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 border-primary flex items-center gap-2 has-changes" data-id="${tempId}" data-new="true">
                <span class="link-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="bi bi-grip-vertical"></i>
                </span>
                <input type="text" value=""
                       class="link-name input-text text-sm py-1 w-36 min-w-[9rem]"
                       placeholder="{{ __('global.name') }}"
                       data-field="name"
                       onchange="markChanged(this)">
                <input type="text" value=""
                       class="link-url input-text text-sm py-1 w-44"
                       placeholder="/url"
                       data-field="url"
                       onchange="markChanged(this)">
                <select class="link-linktype input-text text-xs py-1 w-20" data-field="link_type" onchange="markChanged(this)">
                    <option value="link" selected>Link</option>
                    <option value="new_tab">New tab</option>
                </select>
                <button type="button" onclick="deleteLink(this)" class="btn-delete p-1 text-red-400 hover:text-red-600">
                    <i class="bi bi-trash text-xs"></i>
                </button>
            </div>
        `;

        linksContainer.insertAdjacentHTML('beforeend', html);
        hasChanges = true;
        saveButton.disabled = false;

        linksContainer.lastElementChild.querySelector('.link-name').focus();
        triggerAutoSave(true);
    };

    // Delete section
    window.deleteSection = function(button) {
        const section = button.closest('.section-item');
        const sectionId = section.dataset.id;

        // Delete section and all its children via API if not new
        if (!section.dataset.new) {
            // Delete all children first
            const links = section.querySelectorAll('.link-item');
            links.forEach(link => {
                if (!link.dataset.new) {
                    fetch('/admin/menulink/' + link.dataset.id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                }
            });
            // Delete section
            fetch('/admin/menulink/' + sectionId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        }

        section.remove();
        updateSectionUI();
        triggerAutoSave(true);
    };

    // Delete link
    window.deleteLink = function(button) {
        const link = button.closest('.link-item');
        const linkId = link.dataset.id;

        if (!link.dataset.new) {
            fetch('/admin/menulink/' + linkId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        }

        link.remove();
        triggerAutoSave(true);
    };

    // Move section up/down
    window.moveSection = function(button, direction) {
        const section = button.closest('.section-item');
        const sections = Array.from(container.querySelectorAll('.section-item'));
        const index = sections.indexOf(section);
        const newIndex = index + direction;

        if (newIndex < 0 || newIndex >= sections.length) return;

        if (direction === -1) {
            container.insertBefore(section, sections[newIndex]);
        } else {
            container.insertBefore(sections[newIndex], section);
        }

        hasChanges = true;
        saveButton.disabled = false;
        updateSectionUI();
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

        console.log(`[AJAX] ${method} ${url}`, data);

        try {
            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                console.error(`[AJAX ERROR]`, result);
                return { success: false, error: result };
            }

            console.log(`[AJAX SUCCESS]`, result);
            return { success: true, data: result };
        } catch (error) {
            console.error(`[AJAX EXCEPTION]`, error);
            return { success: false, error: error.message };
        }
    }

    // Save all changes (auto-save)
    async function saveAll() {
        if (isSaving || !hasChanges) return;
        isSaving = true;

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i>Sauvegarde...';

        const sections = container.querySelectorAll('.section-item');
        const order = [];
        let hasError = false;

        console.log('[SAVE] Starting save for', sections.length, 'sections');

        for (let i = 0; i < sections.length; i++) {
            const section = sections[i];
            const isNewSection = section.dataset.new;
            let sectionId = section.dataset.id;

            // Section data (parent)
            const sectionData = {
                name: section.querySelector('.section-name').value,
                url: '#',
                link_type: 'dropdown',
                allowed_role: 'all',
                type: type,
                position: i + 1
            };

            console.log(`[SAVE] Section ${i + 1}:`, { isNewSection, sectionId, sectionData });

            // Create or update section
            if (isNewSection) {
                const result = await ajaxRequest('/admin/menulink/' + type, 'POST', sectionData);
                if (result.success && result.data.id) {
                    sectionId = result.data.id;
                    section.dataset.id = sectionId;
                    delete section.dataset.new;
                } else {
                    hasError = true;
                    console.error('[SAVE] Section creation failed:', result.error);
                    continue;
                }
            } else if (section.classList.contains('has-changes')) {
                const result = await ajaxRequest('/admin/menulink/' + sectionId, 'PUT', sectionData);
                if (!result.success) hasError = true;
            }

            // Process links in this section
            const links = section.querySelectorAll('.link-item');
            const childrenIds = [];

            for (let j = 0; j < links.length; j++) {
                const link = links[j];
                const isNewLink = link.dataset.new;
                let linkId = link.dataset.id;

                const linkData = {
                    name: link.querySelector('.link-name').value,
                    url: link.querySelector('.link-url').value || '#',
                    link_type: link.querySelector('.link-linktype').value,
                    allowed_role: 'all',
                    type: type,
                    position: j + 1,
                    parent_id: parseInt(sectionId)
                };

                console.log(`[SAVE] Link ${j + 1} in section ${i + 1}:`, { isNewLink, linkId, linkData });

                if (isNewLink) {
                    const result = await ajaxRequest('/admin/menulink/' + type, 'POST', linkData);
                    if (result.success && result.data.id) {
                        linkId = result.data.id;
                        link.dataset.id = linkId;
                        delete link.dataset.new;
                    } else {
                        hasError = true;
                    }
                } else if (link.classList.contains('has-changes')) {
                    const result = await ajaxRequest('/admin/menulink/' + linkId, 'PUT', linkData);
                    if (!result.success) hasError = true;
                }

                childrenIds.push(linkId);
            }

            order.push({ id: sectionId, children: childrenIds });
        }

        // Save order
        if (!hasError) {
            console.log('[SAVE] Saving order:', order);
            await ajaxRequest('/admin/menulink/' + type + '/sort', 'POST', { items: order });
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
    updateSectionUI();
})();
</script>
@endsection
