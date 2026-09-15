<input type="hidden" name="defaults_present" value="1">
<section class="mt-6" data-domain-defaults>
    <h3 class="font-semibold mb-3">{{ __('provisioning.admin.domain_tlds.tools.nameservers') }}</h3>
    <p class="text-sm text-gray-500">{{ __('provisioning.admin.domain_tlds.tools.defaults_help') }}</p>
    <div class="my-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300">
        <i class="bi bi-info-circle-fill mt-0.5" aria-hidden="true"></i>
        <span>{{ __('provisioning.admin.domain_tlds.tools.nameservers_minimum_help') }}</span>
    </div>
    @error('default_nameservers')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div>
    @enderror
    @error('default_nameserver_ips')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div>
    @enderror
    <div data-nameserver-list class="space-y-3">
        @php($nameserverIps = old('default_nameserver_ips', $item->default_nameserver_ips ?? []))
        @foreach(old('default_nameservers', $item->default_nameservers ?? []) as $index => $nameserver)
        @include('admin.provisioning.domain-tlds.partials.nameserver-row', ['ips' => $nameserverIps[$index] ?? []])
        @endforeach
    </div>
    <button type="button" class="btn btn-secondary mt-3" data-add-nameserver>{{ __('admin.create') }}</button>
    <h3 class="font-semibold mt-6 mb-3">{{ __('provisioning.admin.domain_tlds.tools.records') }}</h3>
    <div data-dns-record-list>
        @foreach(old('default_dns_records', $item->default_dns_records ?? []) as $index => $record)
        @include('admin.provisioning.domain-tlds.partials.dns-row')
        @endforeach
    </div>
    <button type="button" class="btn btn-secondary" data-add-dns>{{ __('admin.create') }}</button>
    <div class="mt-4">@include('admin/shared/checkbox', ['name' => 'apply_default_dns', 'value' => '1', 'label' => __('provisioning.admin.domain_tlds.tools.apply_dns'), 'checked' => old('apply_default_dns', $item->apply_default_dns)])</div>
    <p class="text-sm text-gray-500 mt-2">{{ __('provisioning.admin.domain_tlds.tools.managed_dns_help') }}</p>
    <template data-nameserver-template>
        @include('admin.provisioning.domain-tlds.partials.nameserver-row', ['index' => '__INDEX__', 'nameserver' => '', 'ips' => []])
    </template>
    <template data-dns-template>@include('admin.provisioning.domain-tlds.partials.dns-row', ['index' => '__INDEX__', 'record' => []])</template>
</section>
@once
<script src="{{ Vite::asset('resources/global/js/admin/domain-tlds.js') }}" type="module"></script>
@endonce
