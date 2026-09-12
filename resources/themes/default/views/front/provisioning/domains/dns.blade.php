@php
    $dnsTypes = collect(\App\Http\Controllers\Front\DomainManagementController::ALLOWED_DNS_TYPES)->mapWithKeys(fn ($type) => [$type => $type])->all();
    $ttlOptions = [300 => '5 min', 900 => '15 min', 1800 => '30 min', 3600 => '1 h', 14400 => '4 h', 43200 => '12 h', 86400 => '24 h'];
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:p-6 text-slate-700 dark:text-slate-300" data-dns-manager>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><h2 class="flex items-center gap-3 text-xl font-bold text-slate-900 dark:text-white"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"><i class="bi bi-diagram-3"></i></span>{{ __('provisioning.domain_manager.dns') }}</h2><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.dns_editor.help') }}</p></div>
        <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ trans_choice('provisioning.domain_manager.dns_editor.records_count', count($records), ['count' => count($records)]) }}</span>
    </div>
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
        <div class="hidden grid-cols-[90px_minmax(120px,0.7fr)_minmax(220px,2fr)_90px_44px] gap-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/60 dark:text-slate-400 md:grid"><span>{{ __('provisioning.domain_manager.dns_editor.type') }}</span><span>{{ __('provisioning.domain_manager.dns_editor.name') }}</span><span>{{ __('provisioning.domain_manager.dns_editor.value') }}</span><span>TTL</span><span></span></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($records as $record)
                <div class="grid gap-3 p-4 md:grid-cols-[90px_minmax(120px,0.7fr)_minmax(220px,2fr)_90px_44px] md:items-center">
                    <div><span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 font-mono text-xs font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ strtoupper($record['type'] ?? '') }}</span></div>
                    <div class="min-w-0"><span class="md:hidden text-xs text-slate-400">{{ __('provisioning.domain_manager.dns_editor.name') }}</span><p class="truncate font-mono text-sm text-slate-900 dark:text-white">{{ $record['name'] ?? '' }}</p></div>
                    <div class="min-w-0"><span class="md:hidden text-xs text-slate-400">{{ __('provisioning.domain_manager.dns_editor.value') }}</span><p class="break-all font-mono text-sm">{{ $record['value'] ?? '' }}</p></div>
                    <div class="text-sm text-slate-500">{{ $record['ttl'] ?? 3600 }}s</div>
                    <form method="POST" action="{{ route('front.services.domains.dns.destroy', ['service' => $service, 'record' => $record['id'] ?? '']) }}">@csrf @method('DELETE')<button class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20" aria-label="{{ __('global.delete') }}"><i class="bi bi-trash"></i></button></form>
                </div>
            @empty
                <div class="px-6 py-10 text-center"><i class="bi bi-inboxes text-3xl text-slate-300"></i><p class="mt-3 text-sm text-slate-500">{{ __('provisioning.domain_manager.dns_editor.empty') }}</p></div>
            @endforelse
        </div>
    </div>
    <form method="POST" action="{{ route('front.services.domains.dns.store', ['service' => $service]) }}" class="mt-6 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/60 sm:p-5">
        @csrf
        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.dns_editor.add') }}</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400" data-dns-help></p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-2">@include('shared/select', ['name' => 'type', 'label' => __('provisioning.domain_manager.dns_editor.type'), 'options' => $dnsTypes, 'value' => old('type', 'A')])</div>
            <div class="lg:col-span-3">@include('shared/input', ['name' => 'name', 'label' => __('provisioning.domain_manager.dns_editor.name'), 'value' => old('name', '@'), 'attributes' => ['placeholder' => '@']])</div>
            <div class="lg:col-span-5" data-dns-value-input>@include('shared/input', ['name' => 'value', 'label' => __('provisioning.domain_manager.dns_editor.value'), 'value' => old('value'), 'attributes' => ['placeholder' => '192.0.2.1']])</div>
            <div class="hidden lg:col-span-5" data-dns-value-textarea>@include('shared/textarea', ['name' => 'value', 'label' => __('provisioning.domain_manager.dns_editor.value'), 'value' => old('value'), 'rows' => 3, 'attributes' => ['placeholder' => 'v=spf1 include:example.com ~all']])</div>
            <div class="lg:col-span-2">@include('shared/select', ['name' => 'ttl', 'label' => 'TTL', 'options' => $ttlOptions, 'value' => old('ttl', 3600)])</div>
        </div>
        <div class="mt-4 flex justify-end"><button class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"><i class="bi bi-plus-lg"></i>{{ __('provisioning.domain_manager.dns_editor.add_action') }}</button></div>
    </form>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-dns-manager]').forEach((root) => {
    const type = root.querySelector('[name="type"]'), inputBox = root.querySelector('[data-dns-value-input]'), textareaBox = root.querySelector('[data-dns-value-textarea]');
    const input = inputBox.querySelector('[name="value"]'), textarea = textareaBox.querySelector('[name="value"]'), help = root.querySelector('[data-dns-help]');
    const hints = @json(__('provisioning.domain_manager.dns_editor.type_help'));
    const placeholders = {A:'192.0.2.1',AAAA:'2001:db8::1',CNAME:'target.example.com',MX:'10 mail.example.com',TXT:'v=spf1 include:example.com ~all',NS:'ns1.example.com',SRV:'10 5 443 service.example.com',CAA:'0 issue "letsencrypt.org"'};
    const refresh = () => { const multiline = type.value === 'TXT'; inputBox.classList.toggle('hidden', multiline); textareaBox.classList.toggle('hidden', !multiline); input.disabled = multiline; textarea.disabled = !multiline; (multiline ? textarea : input).placeholder = placeholders[type.value] || ''; help.textContent = hints[type.value] || ''; };
    type.addEventListener('change', refresh); refresh();
}));
</script>
