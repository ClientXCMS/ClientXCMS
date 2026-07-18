<div class="card-heading mb-4 border-b border-gray-200 pb-4 dark:border-gray-700">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('client.subusers.account_access') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('client.subusers.account_access_description') }}</p>
    </div>
</div>

<div data-subuser-section="accesses">
    <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
        <div class="flex items-center gap-3 bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
            <x-avatar :user="$user" size="lg" />
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-gray-800 dark:text-gray-200">{{ $user->fullName }}</p>
                <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
            </div>
            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ __('client.subusers.owner_badge') }}</span>
        </div>

        @forelse ($ownedAccountAccesses as $access)
            <div class="p-4">
                <form method="POST" action="{{ route('front.subusers.accesses.update', $access) }}">
                    @csrf
                    @method('PUT')
                    <div class="flex items-start gap-3">
                        <x-avatar :user="$access->subCustomer" size="lg" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $access->subCustomer->fullName }}</p>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('client.subusers.subuser_badge') }}</span>
                            </div>
                            <p class="text-sm text-gray-500">{{ $access->subCustomer->email }}</p>
                            @include('front.subusers.permissions-form', ['model' => $access, 'permissions' => $subuserPermissions, 'services' => $subuserServices])
                            <div class="mt-4 flex gap-2">
                                <button class="btn btn-primary">{{ __('global.update') }}</button>
                                <button type="submit" form="remove-access-{{ $access->id }}" class="btn btn-danger" onclick="return confirm('{{ __('client.subusers.confirm_revoke') }}')">{{ __('global.delete') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form id="remove-access-{{ $access->id }}" method="POST" action="{{ route('front.subusers.accesses.destroy', $access) }}">@csrf @method('DELETE')</form>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">{{ __('client.subusers.no_active_accesses') }}</p>
        @endforelse
    </div>
</div>

<div class="hidden" data-subuser-section="invite">
    <form method="POST" action="{{ route('front.subusers.store') }}" class="permissions-form">
        @csrf
        <input type="hidden" name="_subuser_form" value="1">
        <div class="max-w-xl">
            @include('shared/input', ['name' => 'email', 'label' => __('global.email'), 'type' => 'email', 'required' => true])
        </div>
        <div class="mt-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="all_services" value="1" class="rounded border-gray-300 text-indigo-600">
                {{ __('client.subusers.all_services') }}
            </label>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($subuserServices as $service)
                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                        <input type="checkbox" name="services[]" value="{{ $service->id }}" class="mt-0.5 rounded border-gray-300 text-indigo-600">
                        <span>{{ $service->excerptName() }}<span class="block text-xs text-gray-500">{{ $service->expires_at?->format('d/m/Y') ?? __('global.onetime') }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            @foreach ($subuserPermissions as $group => $permissions)
                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('permissions.subusers.groups.'.$group) }}</p>
                    <div class="space-y-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="mt-0.5 rounded border-gray-300 text-indigo-600">
                                <span>{{ __('permissions.subusers.'.str_replace('.', '_', $permission)) }}
                                    @if (in_array($permission, \App\Models\Account\CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES, true))
                                        <span class="block text-xs text-gray-500">{{ __('client.subusers.invoice_permissions_auto_granted') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary mt-5">{{ __('client.subusers.invite.submit') }}</button>
    </form>
</div>

<div class="hidden" data-subuser-section="invitations">
    <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
        @forelse ($accountInvitations as $invitation)
            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-medium text-gray-800 dark:text-gray-200">{{ $invitation->email }}</p><p class="text-sm text-gray-500">{{ __('client.subusers.expires_at') }} {{ optional($invitation->expires_at)->format('d/m/Y H:i') }}</p></div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('front.subusers.invitations.resend', $invitation) }}">@csrf<button class="btn btn-secondary">{{ __('client.subusers.resend') }}</button></form>
                    <form method="POST" action="{{ route('front.subusers.invitations.revoke', $invitation) }}">@csrf @method('DELETE')<button class="btn btn-danger">{{ __('global.delete') }}</button></form>
                </div>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">{{ __('global.no_results') }}</p>
        @endforelse
    </div>
</div>

<div class="hidden" data-subuser-section="received">
    <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
        @forelse ($receivedAccountAccesses as $access)
            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-avatar :user="$access->owner" size="lg" />
                    <div><p class="font-medium text-gray-800 dark:text-gray-200">{{ $access->owner->fullName }}</p><p class="text-sm text-gray-500">{{ $access->all_services ? __('client.subusers.all_services') : trans_choice('client.subusers.services_count', $access->services->count(), ['count' => $access->services->count()]) }}</p></div>
                </div>
                <form method="POST" action="{{ route('front.subusers.accesses.destroy', $access) }}" onsubmit="return confirm('{{ __('client.subusers.confirm_leave') }}')">@csrf @method('DELETE')<button class="btn btn-danger">{{ __('client.subusers.leave_access') }}</button></form>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">{{ __('global.no_results') }}</p>
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sectionButtons = document.querySelectorAll('[data-subuser-section-target]');
        if (!sectionButtons.length) return;

        let currentSection = 'accesses';

        const updateSectionButtons = (highlightActiveSection) => {
            sectionButtons.forEach((button) => {
                const active = highlightActiveSection && button.dataset.subuserSectionTarget === currentSection;
                button.classList.toggle('bg-white', active);
                button.classList.toggle('text-indigo-600', active);
                button.classList.toggle('shadow-sm', active);
                button.classList.toggle('dark:bg-gray-700', active);
                button.setAttribute('aria-current', active ? 'page' : 'false');
            });
        };

        const showSection = (section, highlightActiveSection = true) => {
            currentSection = section;
            document.querySelectorAll('[data-subuser-section]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.subuserSection !== section));
            updateSectionButtons(highlightActiveSection);
        };

        let initialSection = 'accesses';
        @if (old('_subuser_form') && $errors->any())
            initialSection = 'invite';
        @endif
        const subusersPane = document.querySelector('#pane-subusers');
        showSection(initialSection, subusersPane && !subusersPane.classList.contains('hidden'));

        document.querySelectorAll('[data-hs-tab]').forEach((tabButton) => {
            tabButton.addEventListener('click', () => {
                updateSectionButtons(tabButton.dataset.hsTab === '#pane-subusers');
            });
        });

        sectionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelector('[data-hs-tab="#pane-subusers"]')?.click();
                showSection(button.dataset.subuserSectionTarget);
            });
        });

        document.querySelectorAll('.permissions-form input[name="all_services"]').forEach((checkbox) => {
            const toggleServices = () => {
                checkbox.closest('form').querySelectorAll('input[name="services[]"]').forEach((service) => {
                    service.disabled = checkbox.checked;
                    service.closest('label')?.classList.toggle('hidden', checkbox.checked);
                    if (checkbox.checked) service.checked = false;
                });
            };

            checkbox.addEventListener('change', toggleServices);
            toggleServices();
        });
    });
</script>
