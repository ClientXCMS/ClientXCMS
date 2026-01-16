<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Improved menu management UI with inline editing and 3-level hierarchical display.
 *
 * Year: 2026
 */
?>

@extends('admin.settings.sidebar')
@section('title', __('personalization.front_menu.title'))

@section('setting')
    <div class="card">
        <div class="card-heading">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('personalization.front_menu.title') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('personalization.menu_links.description') }}
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-primary text-sm" id="saveAllButton" disabled>
                    <i class="bi bi-check-lg mr-1"></i>{{ __('global.save') }}
                </button>
                <button type="button" class="btn btn-secondary text-sm" onclick="addMenuSection()">
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
                <span>{{ __('personalization.front_menu.help') }}</span>
            </p>
        </div>

        <div id="menu-sections-container" class="space-y-4">
            @foreach ($menus as $menu)
            @php
                $menuName = $menu->trans('name', $menu->name);
                $menuUrl = $menu->trans('url', $menu->url);
            @endphp
            <div class="menu-section p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700" data-id="{{ $menu->id }}">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="section-number inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-bold">{{ $loop->iteration }}</span>
                        <div class="flex items-center gap-2 flex-1">
                            <input type="text" value="{{ $menu->icon }}"
                                   class="menu-icon input-text text-sm py-1.5 w-32"
                                   placeholder="bi bi-house"
                                   data-field="icon"
                                   onchange="markChanged(this)">
                            <input type="text" value="{{ $menuName }}"
                                   class="menu-name input-text text-sm py-1.5 w-40 min-w-[10rem]"
                                   placeholder="{{ __('global.name') }}"
                                   data-field="name"
                                   onchange="markChanged(this)">
                            <button type="button" class="btn-translate p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded" onclick="openTranslation({{ $menu->id }})" title="Traductions">
                                <i class="bi bi-translate"></i>
                            </button>
                            <input type="text" value="{{ $menuUrl }}"
                                   class="menu-url input-text text-sm py-1.5 w-48"
                                   placeholder="/url"
                                   data-field="url"
                                   onchange="markChanged(this)">
                        </div>
                    </div>
                    <div class="flex items-center gap-1 ml-3">
                        <select class="menu-role input-text text-xs py-1 w-20" data-field="allowed_role" onchange="markChanged(this)">
                            <option value="all" {{ $menu->allowed_role == 'all' ? 'selected' : '' }}>Tous</option>
                            <option value="logged" {{ $menu->allowed_role == 'logged' ? 'selected' : '' }}>Connecte</option>
                            <option value="customer" {{ $menu->allowed_role == 'customer' ? 'selected' : '' }}>Client</option>
                            <option value="admin" {{ $menu->allowed_role == 'admin' ? 'selected' : '' }}>Staff</option>
                        </select>
                        <select class="menu-status input-text text-xs py-1 w-24" data-field="status" onchange="markChanged(this)">
                            <option value="active" {{ ($menu->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('personalization.menu_status.active') }}</option>
                            <option value="soon" {{ ($menu->status ?? 'active') == 'soon' ? 'selected' : '' }}>{{ __('personalization.menu_status.soon') }}</option>
                            <option value="maintenance" {{ ($menu->status ?? 'active') == 'maintenance' ? 'selected' : '' }}>{{ __('personalization.menu_status.maintenance') }}</option>
                            <option value="disabled" {{ ($menu->status ?? 'active') == 'disabled' ? 'selected' : '' }}>{{ __('personalization.menu_status.disabled') }}</option>
                        </select>
                        <button type="button" onclick="moveMenuSection(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Monter">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" onclick="moveMenuSection(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed" title="Descendre">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" onclick="deleteMenuSection(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                @if ($menu->hasChildren())
                <div class="section-children space-y-3 ml-8 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    @foreach ($menu->children()->orderBy('position')->get() as $child)
                    @php
                        $childName = $child->trans('name', $child->name);
                        $childUrl = $child->trans('url', $child->url);
                        $hasGrandchildren = $child->children()->count() > 0;
                    @endphp
                    <div class="menu-child {{ $hasGrandchildren ? 'menu-category' : '' }} p-2 bg-gray-50 dark:bg-gray-700/50 rounded" data-id="{{ $child->id }}">
                        <div class="flex items-center gap-2">
                            <input type="text" value="{{ $child->icon }}"
                                   class="child-icon input-text text-xs py-1.5 w-28"
                                   placeholder="bi bi-link"
                                   data-field="icon"
                                   onchange="markChanged(this)">
                            <input type="text" value="{{ $childName }}"
                                   class="child-name input-text text-xs py-1.5 w-36 min-w-[9rem] {{ $hasGrandchildren ? 'font-semibold' : '' }}"
                                   placeholder="{{ __('global.name') }}"
                                   data-field="name"
                                   onchange="markChanged(this)">
                            <button type="button" class="btn-translate p-1 text-blue-500 hover:text-blue-700 text-xs" onclick="openTranslation({{ $child->id }})" title="Traductions">
                                <i class="bi bi-translate"></i>
                            </button>
                            <input type="text" value="{{ $childUrl }}"
                                   class="child-url input-text text-xs py-1.5 w-44 min-w-[11rem]"
                                   placeholder="/url"
                                   data-field="url"
                                   onchange="markChanged(this)">
                            <button type="button" onclick="deleteMenuChild(this)" class="p-1 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>

                        {{-- Level 3: Grandchildren (items under categories) --}}
                        @if ($hasGrandchildren)
                        <div class="grandchildren-container space-y-1 ml-6 mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                            @foreach ($child->children()->orderBy('position')->get() as $grandchild)
                            @php
                                $grandchildName = $grandchild->trans('name', $grandchild->name);
                                $grandchildUrl = $grandchild->trans('url', $grandchild->url);
                            @endphp
                            <div class="menu-grandchild flex items-center gap-2 p-1.5 bg-white dark:bg-gray-800 rounded text-xs" data-id="{{ $grandchild->id }}">
                                <span class="text-gray-400 text-xs"><i class="bi bi-arrow-return-right"></i></span>
                                <input type="text" value="{{ $grandchild->icon }}"
                                       class="grandchild-icon input-text text-xs py-1 w-24"
                                       placeholder="bi bi-link"
                                       data-field="icon"
                                       onchange="markChanged(this)">
                                <input type="text" value="{{ $grandchildName }}"
                                       class="grandchild-name input-text text-xs py-1 w-32 min-w-[8rem]"
                                       placeholder="{{ __('global.name') }}"
                                       data-field="name"
                                       onchange="markChanged(this)">
                                <button type="button" class="btn-translate p-0.5 text-blue-500 hover:text-blue-700 text-xs" onclick="openTranslation({{ $grandchild->id }})" title="Traductions">
                                    <i class="bi bi-translate text-xs"></i>
                                </button>
                                <input type="text" value="{{ $grandchildUrl }}"
                                       class="grandchild-url input-text text-xs py-1 w-36 min-w-[9rem]"
                                       placeholder="/url"
                                       data-field="url"
                                       onchange="markChanged(this)">
                                <button type="button" onclick="deleteGrandchild(this)" class="p-0.5 text-red-400 hover:text-red-600" title="{{ __('global.delete') }}">
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        {{-- Always show button to add level 3 items --}}
                        <button type="button" onclick="addGrandchild(this)"
                                class="mt-1 ml-6 py-1 px-2 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs text-gray-400 hover:border-primary hover:text-primary transition-colors flex items-center gap-1">
                            <i class="bi bi-plus text-xs"></i>
                            Ajouter un lien (niveau 3)
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif

                <button type="button" onclick="addMenuChild(this)"
                        class="mt-3 w-full py-2 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs font-medium text-gray-500 dark:text-gray-400 hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-1">
                    <i class="bi bi-plus"></i>
                    Ajouter un sous-element
                </button>
            </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-2 mt-4">
            <button type="button" onclick="addMenuSection()"
                    class="flex-1 py-2.5 px-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:border-primary hover:text-primary dark:hover:border-primary dark:hover:text-primary transition-colors flex items-center justify-center gap-2">
                <i class="bi bi-plus-circle"></i>
                {{ __('personalization.addelement') }}
            </button>
        </div>
    </div>

    {{-- Translation overlays for all menu items --}}
    @php($locales = \App\Services\Core\LocaleService::getLocales(true, true))
    @foreach ($menus as $menu)
    <div id="translations-overlay-menu-{{ $menu->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $menu->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $menu->id }}">
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">{{ __('personalization.menu_status.message_label') }}</label>
                <input type="text" name="translations[{{ $_key }}][status_message]" value="{{ $menu->trans('status_message', '', $_key) }}" class="input-text w-full" placeholder="{{ __('personalization.menu_status.message_placeholder') }}">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @foreach ($menu->children()->orderBy('position')->get() as $child)
    <div id="translations-overlay-menu-{{ $child->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $child->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $child->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.index') }}" class="p-4">
            <input type="hidden" name="model" value="{{ get_class($child) }}">
            <input type="hidden" name="model_id" value="{{ $child->id }}">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-4 mb-2">{{ $locale['name'] }}</h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('global.name') }}</label>
                <input type="text" name="translations[{{ $_key }}][name]" value="{{ $child->trans('name', '', $_key) }}" class="input-text w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">{{ __('personalization.menu_status.message_label') }}</label>
                <input type="text" name="translations[{{ $_key }}][status_message]" value="{{ $child->trans('status_message', '', $_key) }}" class="input-text w-full" placeholder="{{ __('personalization.menu_status.message_placeholder') }}">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @foreach ($child->children()->orderBy('position')->get() as $grandchild)
    <div id="translations-overlay-menu-{{ $grandchild->id }}" class="overflow-x-hidden overflow-y-auto hs-overlay hs-overlay-open:translate-x-0 translate-x-full fixed top-0 end-0 transition-all duration-300 transform h-full max-w-lg w-full z-[80] bg-white border-s dark:bg-gray-800 dark:border-gray-700 hidden" tabindex="-1">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">
                {{ __('admin.locales.subheading') }} - {{ $grandchild->name }}
            </h3>
            <button type="button" class="flex justify-center items-center w-7 h-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#translations-overlay-menu-{{ $grandchild->id }}">
                <span class="sr-only">{{ __('global.closemodal') }}</span>
                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.translations.index') }}" class="p-4">
            <input type="hidden" name="model" value="{{ get_class($grandchild) }}">
            <input type="hidden" name="model_id" value="{{ $grandchild->id }}">
            @csrf
            @foreach ($locales as $_key => $locale)
                <h2 class="font-bold text-gray-800 dark:text-white mt-4 mb-2">{{ $locale['name'] }}</h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('global.name') }}</label>
                <input type="text" name="translations[{{ $_key }}][name]" value="{{ $grandchild->trans('name', '', $_key) }}" class="input-text w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">{{ __('personalization.menu_status.message_label') }}</label>
                <input type="text" name="translations[{{ $_key }}][status_message]" value="{{ $grandchild->trans('status_message', '', $_key) }}" class="input-text w-full" placeholder="{{ __('personalization.menu_status.message_placeholder') }}">
            @endforeach
            <button class="btn btn-primary mt-4">{{ __('global.save') }}</button>
        </form>
    </div>
    @endforeach
    @endforeach
    @endforeach

<script>
(function() {
    const container = document.getElementById('menu-sections-container');
    const saveButton = document.getElementById('saveAllButton');
    const type = 'front';
    const csrfToken = '{{ csrf_token() }}';
    let hasChanges = false;
    let newItemCounter = 0;
    let saveTimeout = null;
    let isSaving = false;

    // Auto-save with debounce (500ms delay for typing)
    function triggerAutoSave(immediate = false) {
        if (saveTimeout) clearTimeout(saveTimeout);
        if (immediate) {
            saveAll();
        } else {
            saveTimeout = setTimeout(() => saveAll(), 500);
        }
    }

    // Mark form as changed and trigger auto-save
    window.markChanged = function(element) {
        hasChanges = true;
        element.closest('.menu-section, .menu-child, .menu-grandchild').classList.add('has-changes');
        triggerAutoSave();
    };

    // Update section numbers and button states
    function updateUI() {
        const sections = container.querySelectorAll('.menu-section');
        sections.forEach((section, index) => {
            section.querySelector('.section-number').textContent = index + 1;
            section.querySelector('.btn-move-up').disabled = index === 0;
            section.querySelector('.btn-move-down').disabled = index === sections.length - 1;
        });
    }

    // Add new menu section
    window.addMenuSection = function() {
        newItemCounter++;
        const tempId = 'new-' + newItemCounter;
        const index = container.querySelectorAll('.menu-section').length + 1;

        const html = `
            <div class="menu-section p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 border-primary has-changes" data-id="${tempId}" data-new="true">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="section-number inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-bold">${index}</span>
                        <div class="flex items-center gap-2 flex-1">
                            <input type="text" value=""
                                   class="menu-icon input-text text-sm py-1.5 w-32"
                                   placeholder="bi bi-house"
                                   data-field="icon"
                                   onchange="markChanged(this)">
                            <input type="text" value=""
                                   class="menu-name input-text text-sm py-1.5 w-40 min-w-[10rem]"
                                   placeholder="{{ __('global.name') }}"
                                   data-field="name"
                                   onchange="markChanged(this)">
                            <input type="text" value=""
                                   class="menu-url input-text text-sm py-1.5 w-48"
                                   placeholder="/url"
                                   data-field="url"
                                   onchange="markChanged(this)">
                        </div>
                    </div>
                    <div class="flex items-center gap-1 ml-3">
                        <select class="menu-role input-text text-xs py-1 w-20" data-field="allowed_role" onchange="markChanged(this)">
                            <option value="all" selected>Tous</option>
                            <option value="logged">Connecte</option>
                            <option value="customer">Client</option>
                            <option value="admin">Staff</option>
                        </select>
                        <select class="menu-status input-text text-xs py-1 w-24" data-field="status" onchange="markChanged(this)">
                            <option value="active" selected>{{ __('personalization.menu_status.active') }}</option>
                            <option value="soon">{{ __('personalization.menu_status.soon') }}</option>
                            <option value="maintenance">{{ __('personalization.menu_status.maintenance') }}</option>
                            <option value="disabled">{{ __('personalization.menu_status.disabled') }}</option>
                        </select>
                        <button type="button" onclick="moveMenuSection(this, -1)" class="btn-move-up p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" onclick="moveMenuSection(this, 1)" class="btn-move-down p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" onclick="deleteMenuSection(this)" class="btn-delete p-1.5 text-red-400 hover:text-red-600">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" onclick="addMenuChild(this)"
                        class="mt-3 w-full py-2 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded text-xs font-medium text-gray-500 dark:text-gray-400 hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-1">
                    <i class="bi bi-plus"></i>
                    Ajouter un sous-element
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        hasChanges = true;
        saveButton.disabled = false;
        updateUI();

        container.lastElementChild.querySelector('.menu-name').focus();
        triggerAutoSave(true);
    };

    // Add child to a section (level 2 - category)
    window.addMenuChild = function(button) {
        const section = button.closest('.menu-section');
        const parentId = section.dataset.id;
        newItemCounter++;
        const tempId = 'new-child-' + newItemCounter;

        let childrenContainer = section.querySelector('.section-children');
        if (!childrenContainer) {
            childrenContainer = document.createElement('div');
            childrenContainer.className = 'section-children space-y-3 ml-8 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700';
            button.before(childrenContainer);
        }

        const html = `
            <div class="menu-child p-2 bg-gray-50 dark:bg-gray-700/50 rounded has-changes" data-id="${tempId}" data-new="true" data-parent="${parentId}">
                <div class="flex items-center gap-2">
                    <input type="text" value=""
                           class="child-icon input-text text-xs py-1.5 w-28"
                           placeholder="bi bi-link"
                           data-field="icon"
                           onchange="markChanged(this)">
                    <input type="text" value=""
                           class="child-name input-text text-xs py-1.5 w-36 min-w-[9rem]"
                           placeholder="{{ __('global.name') }}"
                           data-field="name"
                           onchange="markChanged(this)">
                    <input type="text" value=""
                           class="child-url input-text text-xs py-1.5 w-44 min-w-[11rem]"
                           placeholder="/url"
                           data-field="url"
                           onchange="markChanged(this)">
                    <button type="button" onclick="deleteMenuChild(this)" class="p-1 text-red-400 hover:text-red-600">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
        `;

        childrenContainer.insertAdjacentHTML('beforeend', html);
        hasChanges = true;

        childrenContainer.lastElementChild.querySelector('.child-name').focus();
        triggerAutoSave(true);
    };

    // Add grandchild (level 3 - item under category)
    window.addGrandchild = function(button) {
        const childContainer = button.closest('.menu-child');
        const parentId = childContainer.dataset.id;
        newItemCounter++;
        const tempId = 'new-grandchild-' + newItemCounter;

        let grandchildrenContainer = childContainer.querySelector('.grandchildren-container');
        if (!grandchildrenContainer) {
            grandchildrenContainer = document.createElement('div');
            grandchildrenContainer.className = 'grandchildren-container space-y-1 ml-6 mt-2 pt-2 border-t border-gray-200 dark:border-gray-600';
            button.before(grandchildrenContainer);
        }

        const html = `
            <div class="menu-grandchild flex items-center gap-2 p-1.5 bg-white dark:bg-gray-800 rounded text-xs has-changes" data-id="${tempId}" data-new="true" data-parent="${parentId}">
                <span class="text-gray-400 text-xs"><i class="bi bi-arrow-return-right"></i></span>
                <input type="text" value=""
                       class="grandchild-icon input-text text-xs py-1 w-24"
                       placeholder="bi bi-link"
                       data-field="icon"
                       onchange="markChanged(this)">
                <input type="text" value=""
                       class="grandchild-name input-text text-xs py-1 w-32 min-w-[8rem]"
                       placeholder="{{ __('global.name') }}"
                       data-field="name"
                       onchange="markChanged(this)">
                <input type="text" value=""
                       class="grandchild-url input-text text-xs py-1 w-36 min-w-[9rem]"
                       placeholder="/url"
                       data-field="url"
                       onchange="markChanged(this)">
                <button type="button" onclick="deleteGrandchild(this)" class="p-0.5 text-red-400 hover:text-red-600">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        `;

        grandchildrenContainer.insertAdjacentHTML('beforeend', html);
        hasChanges = true;

        grandchildrenContainer.lastElementChild.querySelector('.grandchild-name').focus();
        triggerAutoSave(true);
    };

    // Delete section
    window.deleteMenuSection = function(button) {
        const section = button.closest('.menu-section');
        const id = section.dataset.id;

        if (!section.dataset.new) {
            fetch('/admin/menulink/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        }

        section.remove();
        updateUI();
        triggerAutoSave(true);
    };

    // Delete child
    window.deleteMenuChild = function(button) {
        const child = button.closest('.menu-child');
        const id = child.dataset.id;

        if (!child.dataset.new) {
            fetch('/admin/menulink/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        }

        child.remove();
        triggerAutoSave(true);
    };

    // Delete grandchild
    window.deleteGrandchild = function(button) {
        const grandchild = button.closest('.menu-grandchild');
        const id = grandchild.dataset.id;

        if (!grandchild.dataset.new) {
            fetch('/admin/menulink/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        }

        grandchild.remove();
        triggerAutoSave(true);
    };

    // Move section up/down
    window.moveMenuSection = function(button, direction) {
        const section = button.closest('.menu-section');
        const sections = Array.from(container.querySelectorAll('.menu-section'));
        const index = sections.indexOf(section);
        const newIndex = index + direction;

        if (newIndex < 0 || newIndex >= sections.length) return;

        if (direction === -1) {
            container.insertBefore(section, sections[newIndex]);
        } else {
            container.insertBefore(sections[newIndex], section);
        }

        hasChanges = true;
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

    // Save all changes
    async function saveAll() {
        if (isSaving) return;
        isSaving = true;
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i>Sauvegarde...';

        const sections = container.querySelectorAll('.menu-section');
        const order = [];

        for (let i = 0; i < sections.length; i++) {
            const section = sections[i];
            const isNew = section.dataset.new;
            let sectionId = section.dataset.id;

            const hasChildren = section.querySelector('.section-children');
            const sectionData = {
                name: section.querySelector('.menu-name').value,
                url: section.querySelector('.menu-url').value || '#',
                icon: section.querySelector('.menu-icon').value,
                allowed_role: section.querySelector('.menu-role').value,
                status: section.querySelector('.menu-status')?.value || 'active',
                link_type: hasChildren ? 'dropdown' : 'link',
                type: type,
                position: i + 1
            };

            if (isNew) {
                const response = await fetch('/admin/menulink/' + type, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(sectionData)
                });
                const result = await response.json();
                if (result.id) {
                    sectionId = result.id;
                    section.dataset.id = sectionId;
                    delete section.dataset.new;
                }
            } else if (section.classList.contains('has-changes')) {
                await fetch('/admin/menulink/' + sectionId, {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(sectionData)
                });
            }

            // Handle children (level 2 - categories)
            const children = section.querySelectorAll(':scope > .section-children > .menu-child');
            const childOrder = [];

            for (let j = 0; j < children.length; j++) {
                const child = children[j];
                const isNewChild = child.dataset.new;
                let childId = child.dataset.id;

                const hasGrandchildren = child.querySelector('.grandchildren-container');
                const childData = {
                    name: child.querySelector('.child-name').value,
                    url: child.querySelector('.child-url').value || '#',
                    icon: child.querySelector('.child-icon').value,
                    allowed_role: 'all',
                    link_type: hasGrandchildren ? 'dropdown' : 'link',
                    type: type,
                    parent_id: parseInt(sectionId),
                    position: j + 1
                };

                if (isNewChild) {
                    const response = await fetch('/admin/menulink/' + type, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(childData)
                    });
                    const result = await response.json();
                    if (result.id) {
                        childId = result.id;
                        child.dataset.id = childId;
                        delete child.dataset.new;
                    }
                } else if (child.classList.contains('has-changes')) {
                    await fetch('/admin/menulink/' + childId, {
                        method: 'PUT',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(childData)
                    });
                }

                // Handle grandchildren (level 3 - items)
                const grandchildren = child.querySelectorAll('.menu-grandchild');
                for (let k = 0; k < grandchildren.length; k++) {
                    const grandchild = grandchildren[k];
                    const isNewGrandchild = grandchild.dataset.new;
                    let grandchildId = grandchild.dataset.id;

                    const grandchildData = {
                        name: grandchild.querySelector('.grandchild-name').value,
                        url: grandchild.querySelector('.grandchild-url').value || '#',
                        icon: grandchild.querySelector('.grandchild-icon').value,
                        allowed_role: 'all',
                        link_type: 'link',
                        type: type,
                        parent_id: parseInt(childId),
                        position: k + 1
                    };

                    if (isNewGrandchild) {
                        const response = await fetch('/admin/menulink/' + type, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(grandchildData)
                        });
                        const result = await response.json();
                        if (result.id) {
                            grandchildId = result.id;
                            grandchild.dataset.id = grandchildId;
                            delete grandchild.dataset.new;
                        }
                    } else if (grandchild.classList.contains('has-changes')) {
                        await fetch('/admin/menulink/' + grandchildId, {
                            method: 'PUT',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(grandchildData)
                        });
                    }
                }

                childOrder.push(childId);
            }

            order.push({ id: sectionId, children: childOrder });
        }

        // Save order
        await fetch('/admin/menulink/' + type + '/sort', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ items: order })
        });

        // Clear change markers
        container.querySelectorAll('.has-changes').forEach(el => el.classList.remove('has-changes'));
        hasChanges = false;
        isSaving = false;

        saveButton.innerHTML = '<i class="bi bi-check-lg mr-1 text-green-500"></i>Sauvegarde';
        setTimeout(() => {
            saveButton.innerHTML = '<i class="bi bi-cloud-check mr-1"></i>Auto-save';
        }, 1500);
    }

    // Button click also triggers save
    saveButton.addEventListener('click', () => saveAll());

    // Initial UI update
    updateUI();
})();
</script>
@endsection
