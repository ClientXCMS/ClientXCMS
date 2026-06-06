@php($selectedTld = old('tld', request('tld', $data['tld'] ?? '')))
@php($domain = old('domain', request('domain', $data['domain'] ?? '')))
<div class="grid md:grid-cols-2 gap-4 dark:text-gray-400">
    <div>
        @include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => $domain])
    </div>
    <div>
        @include('shared/select', ['name' => 'tld', 'label' => __('provisioning.domain_manager.tld'), 'options' => $tlds, 'value' => $selectedTld])
    </div>
</div>
