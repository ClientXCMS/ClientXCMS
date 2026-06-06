<div class="mt-3 space-y-4 permissions-form">
    <div>
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="all_services" value="1" class="rounded border-gray-300 text-blue-600" @checked($model->all_services)>
            {{ __('client.subusers.all_services') }}
        </label>
        <div class="mt-2 space-y-1.5">
            @foreach ($services as $service)
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="services[]" value="{{ $service->id }}" class="rounded border-gray-300 text-blue-600" @checked($model->services->contains('id', $service->id))>
                    {{ $service->excerptName() }} - {{ $service->expires_at?->format('d/m/Y') ?? __('global.onetime') }} -                                             <x-service-days-remaining expires_at="{{ $service->expires_at }}" state="{{ $service->status }}"></x-service-days-remaining>
                </label>
            @endforeach
        </div>
    </div>
    <div class="grid gap-5 lg:grid-cols-12">
        @foreach ($permissions as $group => $items)
            <div class="{{ $group === 'services' ? 'lg:col-span-8' : 'lg:col-span-4' }}">
                <p class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('permissions.subusers.groups.' . $group) }}</p>
                <div class="{{ $group === 'services' ? 'grid gap-2 sm:grid-cols-2' : 'space-y-1.5' }}">
                    @foreach ($items as $permission)
                        <div>
                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300 text-blue-600" @checked(in_array($permission, $model->permissions ?? [], true))>
                                {{ __('permissions.subusers.' . str_replace('.', '_', $permission)) }}
                            </label>
                            @if (in_array($permission, \App\Models\Account\CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES, true))
                                <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">{{ __('client.subusers.invoice_permissions_auto_granted') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
<script >
    document.addEventListener('DOMContentLoaded', function () {
        const allServicesCheckbox = document.querySelectorAll('.permissions-form input[name="all_services"]');
        const serviceCheckboxes = document.querySelectorAll('.permissions-form input[name="services[]"]');

        function toggleServiceCheckboxes(e) {
            const isChecked = e.target.checked;
            serviceCheckboxes.forEach(checkbox => {
                // hidden input will still submit value, so we disable them instead of hiding
                checkbox.disabled = isChecked;
                if (isChecked) {
                    checkbox.checked = false;
                    checkbox.parentElement.classList.add('hidden');
                } else {
                    checkbox.parentElement.classList.remove('hidden');
                }
            });
        }
    
        allServicesCheckbox.forEach(checkbox => {
            checkbox.addEventListener('change', toggleServiceCheckboxes);
            toggleServiceCheckboxes({ target: checkbox });
        });
    });
</script>
