<div class="card-heading">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ $item->exists ? __($translatePrefix . '.show.title', ['name' => $item->extension]) : __($translatePrefix . '.create.title') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translatePrefix . '.create.subheading') }}</p>
    </div>
    <button class="btn btn-primary">{{ $item->exists ? __('global.save') : __('admin.create') }}</button>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        @include('admin/shared/input', ['name' => 'extension', 'label' => __($translatePrefix . '.extension'), 'value' => old('extension', $item->extension), 'placeholder' => '.com'])
    </div>
    <div>
        @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
    </div>
<div>
    @include('admin/shared/select', ['name' => 'server_id', 'label' => __($translatePrefix . '.server'), 'options' => $servers, 'value' => old('server_id', $item->server_id), 'nullable' => true])
</div>
</div>
    @include('admin/shared/checkbox', ['name' => 'dns_management', 'label' => __('provisioning.domain_manager.dns'), 'checked' => old('dns_management', $item->dns_management)])
    @include('admin/shared/checkbox', ['name' => 'whois_privacy', 'label' => __('provisioning.domain_manager.whois_privacy'), 'checked' => old('whois_privacy', $item->whois_privacy)])
<h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mt-4">{{ __($translatePrefix . '.prices') }}</h3>

<div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead>
            <tr>
                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                    {{ __('admin.products.tariff') }}
                </th>
                @foreach($recurrings as $recurring)
                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                        {{ $recurring['translate'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($actions as $action => $actionLabel)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $actionLabel }} - {{ __('store.price') }}
                    </td>
                    @foreach($recurrings as $billing => $recurring)
                        @php($current = $item->exists ? $item->prices->where('currency', $defaultCurrency)->where('action', $action)->where('billing', $billing)->first() : null)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                            @include('admin/shared/input', [
                                'name' => "prices[$defaultCurrency][$action][$billing][price]",
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => 0,
                                'value' => old("prices.$defaultCurrency.$action.$billing.price", $current?->price),
                            ])
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $actionLabel }} - {{ __('store.fees') }}
                    </td>
                    @foreach($recurrings as $billing => $recurring)
                        @php($current = $item->exists ? $item->prices->where('currency', $defaultCurrency)->where('action', $action)->where('billing', $billing)->first() : null)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                            @include('admin/shared/input', [
                                'name' => "prices[$defaultCurrency][$action][$billing][setup]",
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => 0,
                                'value' => old("prices.$defaultCurrency.$action.$billing.setup", $current?->setup),
                            ])
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
