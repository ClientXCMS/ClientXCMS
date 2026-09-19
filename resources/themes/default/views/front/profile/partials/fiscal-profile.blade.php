@php
$customer = $user;
$profileStatus = app(\App\Services\Billing\FiscalProfileService::class)->status($customer);
@endphp

<div class="flex flex-col gap-3 pb-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.profile.title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('einvoicing.profile.description') }}</p>
    </div>
    <span class="inline-flex items-center gap-2 self-start rounded-full px-3 py-1 text-sm font-medium {{ $profileStatus === 'complete' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($profileStatus === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300') }}">
        <span class="size-2 rounded-full {{ $profileStatus === 'complete' ? 'bg-green-500' : ($profileStatus === 'pending' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
        {{ __('einvoicing.status.'.$profileStatus) }}
    </span>
</div>

<form method="POST" action="{{ route('front.billing-profile.update') }}" class="mt-6 space-y-6">
    @csrf
    @method('PUT')
    <input type="hidden" name="_fiscal_profile_form" value="1">
    @if(request('return_to'))
    <input type="hidden" name="return_to" value="{{ request('return_to') }}">
    @endif

    <section>
        <h3 class="mb-4 font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.identity') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>

                @include('shared.select', ['name' => 'customer_type', 'label' => __('einvoicing.profile.type'), 'options' => ['individual' => __('einvoicing.profile.individual'), 'business' => __('einvoicing.profile.business'), 'association' => __('einvoicing.profile.association')], 'value' => old('customer_type', $customer->customer_type)])
            </div>
            <div id="front-fiscal-business-fields" class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-2">
                <div class="md:col-span-2 space-y-4">
                    @foreach(app(\App\Services\Billing\FiscalProfileExtensionRegistry::class)->views() as $extensionView)
                        @include($extensionView, ['customer' => $customer, 'context' => 'front'])
                    @endforeach
                </div>
                <div>
                    @include('shared.input', ['name' => 'legal_name', 'label' => __('einvoicing.profile.legal_name'), 'value' => old('legal_name', $customer->legal_name), 'optional' => true])
                </div>
                <div id="front-fiscal-association-status">
                    @include('shared.select', ['name' => 'tax_subject_status', 'label' => __('einvoicing.profile.tax_subject_status'), 'options' => ['unknown' => __('einvoicing.profile.tax_status_unknown'), 'non_taxable' => __('einvoicing.profile.tax_status_non_taxable'), 'taxable_not_vat_liable' => __('einvoicing.profile.tax_status_taxable_not_vat_liable'), 'vat_liable' => __('einvoicing.profile.tax_status_vat_liable')], 'value' => old('tax_subject_status', $customer->tax_subject_status ?? 'unknown'), 'help' => __('einvoicing.profile.tax_subject_status_help')])
                </div>
                <div id="front-fiscal-rna">
                    @include('shared.input', ['name' => 'rna_number', 'label' => __('einvoicing.profile.rna_number'), 'value' => old('rna_number', $customer->rna_number), 'optional' => true])
                </div>
                <div id="front-fiscal-siren">
                    @include('shared.input', ['name' => 'siren', 'label' => __('einvoicing.profile.siren'), 'value' => old('siren', $customer->siren), 'optional' => true])
                </div>
                <div id="front-fiscal-siret">
                    @include('shared.input', ['name' => 'siret', 'label' => __('einvoicing.profile.siret'), 'value' => old('siret', $customer->siret), 'optional' => true])
                </div>
                <div id="front-fiscal-tax-registration">
                    @include('shared.input', ['name' => 'tax_registration_number', 'label' => __('einvoicing.profile.tax_registration_number'), 'value' => old('tax_registration_number', $customer->tax_registration_number), 'optional' => true])
                </div>
                <div id="front-fiscal-vat">
                    @include('shared.input', ['name' => 'vat_number', 'label' => __('einvoicing.profile.vat_number'), 'value' => old('vat_number', $customer->vat_number), 'optional' => true])
                </div>
            </div>
        </div>
    </section>

    <section>
        <h3 class="mb-4 font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.address') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                @include('shared.input', ['name' => 'address', 'label' => __('global.address'), 'value' => old('address', $customer->address)])
            </div>
            <div>
                @include('shared.input', ['name' => 'address2', 'label' => __('global.address2'), 'value' => old('address2', $customer->address2), 'optional' => true])
            </div>
            <div>
                @include('shared.input', ['name' => 'zipcode', 'label' => __('global.zip'), 'value' => old('zipcode', $customer->zipcode)])
            </div>
            <div>
                @include('shared.input', ['name' => 'city', 'label' => __('global.city'), 'value' => old('city', $customer->city)])
            </div>
            <div>
                @include('shared.input', ['name' => 'region', 'label' => __('global.region'), 'value' => old('region', $customer->region)])
            </div>
            <div>
                @include('shared.select', ['name' => 'country', 'label' => __('global.country'), 'options' => $countries, 'value' => old('country', $customer->country)])
            </div>
        </div>
    </section>

    <section>
        @include('shared.textarea', ['name' => 'billing_details', 'label' => __('einvoicing.profile.additional_details'), 'value' => old('billing_details', $customer->billing_details), 'help' => __('einvoicing.profile.additional_details_help'), 'optional' => true])
    </section>

    <button class="btn btn-primary" type="submit"><i class="bi bi-save2 mr-2"></i>{{ __('global.save') }}</button>
</form>
