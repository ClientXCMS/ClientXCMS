<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */
?>

<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="space-y-12">
        <div class="pb-6">
            <div class="border-b border-gray-900/10 pb-6">
                <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "firstname", "label" => __('global.firstname') ])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "lastname", "label" => __('global.lastname')])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email"])
                    </div>

                    <div class="sm:col-span-3">
                        <label for="phone" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">
                            {{ __('global.phone') }}
                            <span class="text-gray-500">({{ __('global.optional') }})</span>
                        </label>
                        <div class="mt-2 relative" id="phone-country-picker">
                            <div class="flex items-stretch rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-slate-900 overflow-hidden">
                                <button type="button" id="phone-country-button" class="px-3 min-w-[120px] text-left text-sm bg-gray-50 dark:bg-slate-800 border-r border-gray-300 dark:border-gray-700 flex items-center justify-between gap-2">
                                    <span id="phone-country-button-label">🇫🇷 +33</span>
                                    <i class="bi bi-chevron-down text-xs"></i>
                                </button>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="flex-1 px-4 py-2.5 bg-transparent outline-none text-gray-900 dark:text-white" placeholder="6 12 34 56 78">
                            </div>

                            <div id="phone-country-dropdown" class="hidden absolute z-20 mt-2 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-lg max-h-64 overflow-hidden">
                                <div class="p-2 border-b border-gray-100 dark:border-gray-800">
                                    <input type="text" id="phone-country-search" class="input-text" placeholder="Rechercher un pays ou indicatif">
                                </div>
                                <div id="phone-country-list" class="max-h-48 overflow-auto"></div>
                            </div>
                        </div>
                        @error('phone')
                            <div class="text-red-500 mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "address", "label" => __('global.address')])
                    </div>
                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "address2", "label" => __('global.address2'), "optional" => true])
                    </div>

                    <div class="sm:col-span-1">
                        @include("shared.input", ["name" => "zipcode", "label" => __('global.zip')])
                    </div>

                    <div class="sm:col-span-2">
                        @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries, "value" => old("country", "FR")])
                    </div>

                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "region", "label" => __('global.region')])
                    </div>
                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "city", "label" => __('global.city')])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.password", ["name" => "password", "label" => __('global.password'), "generate" => true])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.password", ["name" => "password_confirmation", "label" => __('global.password_confirmation')])
                    </div>
                    @if (setting('register.toslink'))
                        <div class="sm:col-span-3 flex gap-x-6 mb-2">
                            @include('shared/checkbox', ['label' => __('auth.register.accept'), 'name' => 'accept_tos'])
                        </div>
                    @endif

                    @if (isset($redirect))
                        <input type="hidden" name="redirect" value="{{ $redirect }}">
                    @endif
                    @include('shared.captcha')

                </div>
            </div>
            <button class="btn-primary block w-full">
                {{ __('auth.register.btn') }}
            </button>
        </div>
    </div>
</form>


@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const meta = @json($countryPhoneMeta ?? {});
        const countries = Object.entries(meta).map(([iso, item]) => ({ iso, ...item }));
        const countrySelect = document.getElementById('country');
        const phoneInput = document.getElementById('phone');
        const button = document.getElementById('phone-country-button');
        const buttonLabel = document.getElementById('phone-country-button-label');
        const dropdown = document.getElementById('phone-country-dropdown');
        const list = document.getElementById('phone-country-list');
        const search = document.getElementById('phone-country-search');

        if (!countrySelect || !button || !buttonLabel || !dropdown || !list || !search) return;

        const renderList = (query = '') => {
            const q = query.toLowerCase().trim();
            const filtered = countries.filter((item) => {
                if (!q) return true;
                return (item.name || '').toLowerCase().includes(q)
                    || (item.iso || '').toLowerCase().includes(q)
                    || (item.dial_code || '').toLowerCase().includes(q);
            });

            list.innerHTML = filtered.map((item) => `
                <button type="button" data-iso="${item.iso}" class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-between">
                    <span class="text-sm">${item.flag || ''} ${item.name || item.iso}</span>
                    <span class="text-sm text-gray-500">${item.dial_code || ''}</span>
                </button>
            `).join('');
        };

        const applyCountry = (iso, setSelect = true) => {
            const item = meta[iso];
            if (!item) return;

            buttonLabel.textContent = `${item.flag || ''} ${item.dial_code || ''}`.trim();
            if (setSelect) {
                countrySelect.value = iso;
            }

            if (phoneInput && !phoneInput.value) {
                phoneInput.placeholder = `${item.dial_code || ''} 6 12 34 56 78`.trim();
            }
        };

        button.addEventListener('click', () => {
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                search.focus();
            }
        });

        list.addEventListener('click', (e) => {
            const target = e.target.closest('button[data-iso]');
            if (!target) return;
            const iso = target.getAttribute('data-iso');
            applyCountry(iso);
            dropdown.classList.add('hidden');
            search.value = '';
            renderList();
        });

        search.addEventListener('input', () => renderList(search.value));

        countrySelect.addEventListener('change', () => applyCountry(countrySelect.value, false));

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#phone-country-picker')) {
                dropdown.classList.add('hidden');
            }
        });

        phoneInput?.addEventListener('blur', () => {
            const current = meta[countrySelect.value];
            if (!current || !current.dial_code || !phoneInput.value) return;
            if (!phoneInput.value.trim().startsWith('+')) {
                phoneInput.value = `${current.dial_code} ${phoneInput.value.trim()}`;
            }
        });

        renderList();
        applyCountry(countrySelect.value || 'FR', false);
    });
</script>
@endsection
