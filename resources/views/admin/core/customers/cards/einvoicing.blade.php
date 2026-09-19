@php
$profileService = app(\App\Services\Billing\FiscalProfileService::class);
$profileStatus = $profileService->status($customer);
@endphp
<form class="card space-y-3" method="POST" action="{{ route('admin.customers.fiscal-profile.update', $customer) }}">
    @csrf
    @method('PUT')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 pb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.title') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('einvoicing.admin.description') }}</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium {{ $profileStatus === 'complete' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($profileStatus === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300') }}">
            <span class="size-2 rounded-full {{ $profileStatus === 'complete' ? 'bg-green-500' : ($profileStatus === 'pending' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
            {{ __('einvoicing.status.'.$profileStatus) }}
        </span>
    </div>

    <section>
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('einvoicing.admin.identity') }}</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @include('admin/shared/select', ['name'=>'customer_type','label'=>__('einvoicing.profile.type'),'options'=>['individual'=>__('einvoicing.profile.individual'),'business'=>__('einvoicing.profile.business'),'association'=>__('einvoicing.profile.association')],'value'=>old('customer_type', $customer->customer_type)])
            <div id="admin-fiscal-business-fields" class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3 space-y-4">
                    @foreach(app(\App\Services\Billing\FiscalProfileExtensionRegistry::class)->views() as $extensionView)
                        @include($extensionView, ['customer' => $customer, 'context' => 'admin'])
                    @endforeach
                </div>
                <div>
                    @include('admin/shared/input', ['name'=>'legal_name','label'=>__('einvoicing.profile.legal_name'),'value'=>old('legal_name', $customer->legal_name),'optional'=>true])
                </div>
                <div id="admin-fiscal-association-status">
                    @include('admin/shared/select', ['name'=>'tax_subject_status','label'=>__('einvoicing.profile.tax_subject_status'),'options'=>['unknown'=>__('einvoicing.profile.tax_status_unknown'),'non_taxable'=>__('einvoicing.profile.tax_status_non_taxable'),'taxable_not_vat_liable'=>__('einvoicing.profile.tax_status_taxable_not_vat_liable'),'vat_liable'=>__('einvoicing.profile.tax_status_vat_liable')],'value'=>old('tax_subject_status', $customer->tax_subject_status ?? 'unknown'),'help'=>__('einvoicing.profile.tax_subject_status_help')])
                </div>
                <div id="admin-fiscal-rna">
                    @include('admin/shared/input', ['name'=>'rna_number','label'=>__('einvoicing.profile.rna_number'),'value'=>old('rna_number', $customer->rna_number),'optional'=>true])
                </div>
                <div id="admin-fiscal-siren">
                    @include('admin/shared/input', ['name'=>'siren','label'=>__('einvoicing.profile.siren'),'value'=>old('siren', $customer->siren),'optional'=>true])
                </div>
                <div id="admin-fiscal-siret">
                    @include('admin/shared/input', ['name'=>'siret','label'=>__('einvoicing.profile.siret'),'value'=>old('siret', $customer->siret),'optional'=>true])
                </div>
                <div id="admin-fiscal-vat">
                    @include('admin/shared/input', ['name'=>'vat_number','label'=>__('einvoicing.profile.vat_number'),'value'=>old('vat_number', $customer->vat_number),'optional'=>true])
                </div>
                <div id="admin-fiscal-tax-registration">
                    @include('admin/shared/input', ['name'=>'tax_registration_number','label'=>__('einvoicing.profile.tax_registration_number'),'value'=>old('tax_registration_number', $customer->tax_registration_number),'optional'=>true])
                </div>
            </div>
        </div>
    </section>

    <section>
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('einvoicing.admin.address') }}</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                @include('admin/shared/input', ['name'=>'address','label'=>__('global.address'),'value'=>old('address', $customer->address)])
            </div>
            <div>
                @include('admin/shared/input', ['name'=>'address2','label'=>__('global.address2'),'value'=>old('address2', $customer->address2),'optional'=>true])
            </div>
            <div>
                @include('admin/shared/input', ['name'=>'zipcode','label'=>__('global.zip'),'value'=>old('zipcode', $customer->zipcode)])
            </div>
            <div>
                @include('admin/shared/input', ['name'=>'city','label'=>__('global.city'),'value'=>old('city', $customer->city)])
            </div>
            <div>
                @include('admin/shared/input', ['name'=>'region','label'=>__('global.region'),'value'=>old('region', $customer->region)])
            </div>
            <div>
                @include('admin/shared/select', ['name'=>'country','label'=>__('global.country'),'options'=>$countries,'value'=>old('country', $customer->country)])
            </div>
        </div>
    </section>

    <section>
        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('einvoicing.profile.additional_details') }}</h4>
        @include('admin/shared/textarea', ['name'=>'billing_details','label'=>__('einvoicing.profile.additional_details'),'value'=>old('billing_details', $customer->billing_details),'help'=>__('einvoicing.profile.additional_details_help'),'optional'=>true])
    </section>

    <button class="btn btn-primary" type="submit"><i class="bi bi-save2 mr-2"></i>{{ __('global.save') }}</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const requestedTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedTab === 'einvoicing') document.getElementById('tabs-einvoicing-item')?.click();
        const type = document.querySelector('#tabs-einvoicing [name="customer_type"]');
        const taxStatus = document.querySelector('#tabs-einvoicing [name="tax_subject_status"]');
        const country = document.querySelector('#tabs-einvoicing [name="country"]');
        const fields = document.getElementById('admin-fiscal-business-fields');
        if (type && fields) {
            const toggle = () => {
                const isOrganization = ['business', 'association'].includes(type.value);
                const isAssociation = type.value === 'association';
                const isFrench = country?.value === 'FR';
                fields.style.display = isOrganization ? '' : 'none';
                document.getElementById('admin-fiscal-association-status').style.display = isAssociation ? '' : 'none';
                document.getElementById('admin-fiscal-rna').style.display = isAssociation ? '' : 'none';
                document.getElementById('admin-fiscal-siren').style.display = isOrganization && isFrench ? '' : 'none';
                document.getElementById('admin-fiscal-siret').style.display = isOrganization && isFrench ? '' : 'none';
                document.getElementById('admin-fiscal-tax-registration').style.display = isOrganization && !isFrench ? '' : 'none';
                document.getElementById('admin-fiscal-vat').style.display = isOrganization && (!isAssociation || taxStatus?.value !== 'non_taxable') ? '' : 'none';
            };
            type.addEventListener('change', toggle);
            taxStatus?.addEventListener('change', toggle);
            country?.addEventListener('change', toggle);
            toggle();
        }
    });
</script>
