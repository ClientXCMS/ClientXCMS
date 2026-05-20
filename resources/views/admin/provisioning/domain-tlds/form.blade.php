<div class="card-heading">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ $item->exists ? __($translatePrefix . '.show.title', ['name' => $item->extension]) : __($translatePrefix . '.create.title') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translatePrefix . '.create.subheading') }}</p>
    </div>
    <button class="btn btn-primary">{{ $item->exists ? __('global.save') : __('admin.create') }}</button>
</div>

<div class="grid md:grid-cols-2 gap-4">
    @include('admin/shared/input', ['name' => 'extension', 'label' => __($translatePrefix . '.extension'), 'value' => old('extension', $item->extension), 'placeholder' => '.com'])
    @include('admin/shared/status-select', ['name' => 'status', 'label' => __('global.status'), 'value' => old('status', $item->status)])
    @include('admin/shared/select', ['name' => 'server_id', 'label' => __($translatePrefix . '.server'), 'options' => $servers, 'value' => old('server_id', $item->server_id), 'nullable' => true])
    @include('admin/shared/checkbox', ['name' => 'dns_management', 'label' => __('provisioning.domain_manager.dns'), 'checked' => old('dns_management', $item->dns_management)])
    @include('admin/shared/checkbox', ['name' => 'whois_privacy', 'label' => __('provisioning.domain_manager.whois_privacy'), 'checked' => old('whois_privacy', $item->whois_privacy)])
</div>

<h3 class="font-semibold uppercase text-gray-600 dark:text-gray-400 mt-4">{{ __($translatePrefix . '.prices') }}</h3>
@foreach($currencies as $currency)
    <div class="mt-4 border rounded-lg p-4 dark:border-gray-700">
        <h4 class="font-semibold dark:text-gray-300">{{ $currency }}</h4>
        @foreach($actions as $action => $actionLabel)
            <h5 class="mt-3 text-sm font-semibold dark:text-gray-400">{{ $actionLabel }}</h5>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($recurrings as $billing => $recurring)
                    @php($current = $item->exists ? $item->prices->where('currency', $currency)->where('action', $action)->where('billing', $billing)->first() : null)
                    <div>
                        @include('admin/shared/input', ['name' => "prices[$currency][$action][$billing][price]", 'label' => $recurring['translate'], 'value' => old("prices.$currency.$action.$billing.price", $current?->price), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                        @include('admin/shared/input', ['name' => "prices[$currency][$action][$billing][setup]", 'label' => __('store.fees'), 'value' => old("prices.$currency.$action.$billing.setup", $current?->setup), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endforeach
