<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ __('client.subusers.index') }}</h3>
    </div>

    <div class="space-y-4">
        @forelse ($accountAccesses as $access)
            <form method="POST" action="{{ route('admin.customers.subusers.update', ['customer' => $item, 'access' => $access]) }}" class="border rounded-lg p-4 dark:border-gray-700">
                @csrf
                @method('PUT')
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $access->subCustomer->fullName }}</p>
                        <p class="text-sm text-gray-500">{{ $access->subCustomer->email }}</p>
                    </div>
                    @if (staff_has_permission('admin.manage_customers'))
                        <button form="admin-delete-access-{{ $access->id }}" class="btn btn-danger btn-sm">{{ __('global.delete') }}</button>
                    @endif
                </div>

                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="all_services" value="1" class="rounded border-gray-300 text-indigo-600" @checked($access->all_services)>
                            {{ __('client.subusers.all_services') }}
                        </label>
                        <div class="mt-3 space-y-2 max-h-40 overflow-y-auto">
                            @foreach ($item->services(true)->orderBy('name')->get() as $service)
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" class="rounded border-gray-300 text-indigo-600" @checked($access->services->contains('id', $service->id))>
                                    {{ $service->excerptName() }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        @foreach ($accountAccessPermissions as $group => $permissions)
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2">{{ __('permissions.subusers.groups.' . $group) }}</p>
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300 text-indigo-600" @checked(in_array($permission, $access->permissions ?? [], true))>
                                    {{ __('permissions.subusers.' . str_replace('.', '_', $permission)) }}
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                @if (staff_has_permission('admin.manage_customers'))
                    <button class="btn btn-secondary mt-4">{{ __('global.update') }}</button>
                @endif
            </form>
            <form id="admin-delete-access-{{ $access->id }}" method="POST" action="{{ route('admin.customers.subusers.destroy', ['customer' => $item, 'access' => $access]) }}">
                @csrf
                @method('DELETE')
            </form>
        @empty
            <p class="text-sm text-gray-500">{{ __('global.no_results') }}</p>
        @endforelse
    </div>

    <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mt-8 mb-4">{{ __('client.subusers.pending_invitations') }}</h3>
    <div class="space-y-3">
        @forelse ($accountInvitations as $invitation)
            <div class="border rounded-lg p-4 flex items-center justify-between gap-4 dark:border-gray-700">
                <div>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $invitation->email }}</p>
                    <p class="text-sm text-gray-500">{{ __('client.subusers.expires_at') }} {{ optional($invitation->expires_at)->format('d/m/Y H:i') }}</p>
                </div>
                @if (staff_has_permission('admin.manage_customers'))
                    <form method="POST" action="{{ route('admin.customers.subusers.invitations.revoke', ['customer' => $item, 'invitation' => $invitation]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">{{ __('global.delete') }}</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">{{ __('global.no_results') }}</p>
        @endforelse
    </div>
</div>
