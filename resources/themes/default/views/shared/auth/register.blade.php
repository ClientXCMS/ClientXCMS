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
                        @include("shared.input", ["name" => "phone", "label" => __('global.phone'), "optional" => true])
                    </div>

                    <div class="sm:col-span-3">
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Indicatif / pays sélectionné</div>
                            <div id="phone-country-meta" class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">-</div>
                        </div>
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
        const meta = @json($countryPhoneMeta ?? []);
        const countrySelect = document.getElementById('country');
        const phoneInput = document.getElementById('phone');
        const metaEl = document.getElementById('phone-country-meta');

        const updatePhoneMeta = () => {
            if (!countrySelect || !metaEl) return;
            const selected = countrySelect.value;
            const item = meta[selected];
            if (!item) {
                metaEl.textContent = '-';
                return;
            }

            const dial = item.dial_code ?? '';
            metaEl.textContent = `${item.flag ?? ''} ${item.name} (${dial}) · Langue: ${item.language ?? 'n/a'}`;

            if (phoneInput && dial && !phoneInput.value) {
                phoneInput.placeholder = `${dial} 6 12 34 56 78`;
            }
        };

        countrySelect?.addEventListener('change', updatePhoneMeta);
        updatePhoneMeta();
    });
</script>
@endsection
