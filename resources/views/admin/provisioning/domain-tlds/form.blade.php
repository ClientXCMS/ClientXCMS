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

@if(count($currencies) > 1)
    <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
        <nav class="flex space-x-2" role="tablist">
            @foreach($currencies as $currency)
                <button type="button"
                    class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 px-4 inline-flex items-center gap-x-2 border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 @if($loop->first) active @endif"
                    id="tab-control-currency-{{ $currency }}" data-hs-tab="#tab-content-currency-{{ $currency }}"
                    aria-controls="tab-content-currency-{{ $currency }}" role="tab">
                    {{ $currency }}
                </button>
            @endforeach
        </nav>
    </div>
@endif

<div>
    @foreach($currencies as $currency)
        <div id="tab-content-currency-{{ $currency }}" class="@if(!$loop->first) hidden @endif" role="tabpanel" aria-labelledby="tab-control-currency-{{ $currency }}">
            <div class="mt-4 border rounded-lg p-4 dark:border-gray-700">
                @if(count($currencies) == 1)
                    <h4 class="font-semibold dark:text-gray-300 mb-4">{{ $currency }}</h4>
                @endif
                
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                    <nav class="flex space-x-2" role="tablist">
                        @foreach($actions as $action => $actionLabel)
                            <button type="button"
                                class="hs-tab-active:font-semibold hs-tab-active:border-blue-600 hs-tab-active:text-blue-600 py-2 px-4 inline-flex items-center gap-x-2 border-b-2 border-transparent text-sm whitespace-nowrap text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 @if($loop->first) active @endif"
                                id="tab-control-{{ $currency }}-{{ $action }}" data-hs-tab="#tab-content-{{ $currency }}-{{ $action }}"
                                aria-controls="tab-content-{{ $currency }}-{{ $action }}" role="tab">
                                {{ $actionLabel }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div>
                    @foreach($actions as $action => $actionLabel)
                        <div id="tab-content-{{ $currency }}-{{ $action }}" class="@if(!$loop->first) hidden @endif" role="tabpanel" aria-labelledby="tab-control-{{ $currency }}-{{ $action }}">
                            <div class="grid md:grid-cols-3 gap-4">
                                @foreach($recurrings as $billing => $recurring)
                                    @php($current = $item->exists ? $item->prices->where('currency', $currency)->where('action', $action)->where('billing', $billing)->first() : null)
                                    <div class="bg-gray-50 dark:bg-slate-900/50 p-4 rounded-xl border border-gray-100 dark:border-slate-800">
                                        <h6 class="font-medium text-gray-800 dark:text-gray-200 mb-2">{{ $recurring['translate'] }}</h6>
                                        @include('admin/shared/input', ['name' => "prices[$currency][$action][$billing][price]", 'label' => __('store.price'), 'value' => old("prices.$currency.$action.$billing.price", $current?->price), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                                        @include('admin/shared/input', ['name' => "prices[$currency][$action][$billing][setup]", 'label' => __('store.fees'), 'value' => old("prices.$currency.$action.$billing.setup", $current?->setup), 'type' => 'number', 'step' => '0.01', 'min' => 0])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
