<input type="hidden" name="defaults_present" value="1">
<section class="mt-6" data-domain-defaults>
    <h3 class="font-semibold mb-3">{{ __('provisioning.admin.domain_tlds.tools.nameservers') }}</h3>
    <p class="text-sm text-gray-500 mb-4">{{ __('provisioning.admin.domain_tlds.tools.defaults_help') }}</p>
    <div data-nameserver-list class="space-y-3">
        @foreach(old('default_nameservers', $item->default_nameservers ?? []) as $ns)
        <div class="flex gap-3" data-dns-row><input class="input-text" name="default_nameservers[]" value="{{ $ns }}" aria-label="{{ __('provisioning.admin.domain_tlds.tools.nameservers') }}"><button type="button" class="btn btn-danger" data-remove-row>{{ __('global.delete') }}</button></div>
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
    <label class="block mt-4"><input type="checkbox" name="apply_default_dns" value="1" @checked(old('apply_default_dns', $item->apply_default_dns))> {{ __('provisioning.admin.domain_tlds.tools.apply_dns') }}</label>
    <p class="text-sm text-gray-500 mt-2">{{ __('provisioning.admin.domain_tlds.tools.managed_dns_help') }}</p>
    <template data-nameserver-template>
        <div class="flex gap-3" data-dns-row><input class="input-text" name="default_nameservers[]" aria-label="{{ __('provisioning.admin.domain_tlds.tools.nameservers') }}"><button type="button" class="btn btn-danger" data-remove-row>{{ __('global.delete') }}</button></div>
    </template>
    <template data-dns-template>@include('admin.provisioning.domain-tlds.partials.dns-row', ['index' => '__INDEX__', 'record' => []])</template>
</section>
@once
<script src="{{ Vite::asset('resources/global/js/admin/domain-tlds.js') }}" type="module"></script>
@endonce