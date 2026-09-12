@php
    $selectedTld = old('tld', request('tld', $data['tld'] ?? ''));
    $domain = old('domain', request('domain', $data['domain'] ?? ''));
    $nameserverMode = old('nameserver_mode', $data['nameserver_mode'] ?? 'managed');
    $currentNameservers = old('nameservers', $data['nameservers'] ?? []);
@endphp
<style>
    [data-domain-order] { --domain-accent: var(--altura-primary, var(--voltaris-primary, #2563eb)); }
    [data-domain-order] .theme-cart-control { accent-color: var(--domain-accent); }
    [data-domain-order] .theme-cart-soft { background: color-mix(in srgb, var(--domain-accent) 12%, white) !important; color: var(--domain-accent) !important; }
    [data-domain-order] .theme-cart-option:hover { border-color: color-mix(in srgb, var(--domain-accent) 70%, white) !important; }
    [data-domain-order] .theme-cart-option:has(input:checked) { border-color: var(--domain-accent) !important; box-shadow: 0 0 0 2px color-mix(in srgb, var(--domain-accent) 35%, transparent) !important; }
    .dark [data-domain-order] .theme-cart-soft { background: color-mix(in srgb, var(--domain-accent) 20%, transparent) !important; }
</style>

<div class="space-y-6 text-slate-700 dark:text-slate-300" data-domain-order data-nameservers='@json($tldNameservers)'>
    <div class="grid md:grid-cols-2 gap-4">
        <div>@include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => $domain])</div>
        <div>@include('shared/select', ['name' => 'tld', 'label' => __('provisioning.domain_manager.tld'), 'options' => $tlds, 'value' => $selectedTld])</div>
    </div>

    <div>
        <div class="mb-3">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.dns_choice.title') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.dns_choice.help') }}</p>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <label class="theme-cart-option relative cursor-pointer rounded-xl border border-slate-200 bg-white p-5 transition dark:border-slate-700 dark:bg-slate-900">
                <input class="theme-cart-control peer sr-only" type="radio" name="nameserver_mode" value="managed" @checked($nameserverMode === 'managed')>
                <span class="flex items-start gap-3">
                    <span class="theme-cart-soft flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"><i class="bi bi-shield-check"></i></span>
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.dns_choice.managed') }}</span>
                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.dns_choice.managed_help') }}</span>
                        <span class="mt-3 flex flex-wrap gap-2" data-managed-nameservers></span>
                    </span>
                </span>
            </label>
            <label class="theme-cart-option relative cursor-pointer rounded-xl border border-slate-200 bg-white p-5 transition dark:border-slate-700 dark:bg-slate-900">
                <input class="theme-cart-control peer sr-only" type="radio" name="nameserver_mode" value="custom" @checked($nameserverMode === 'custom')>
                <span class="flex items-start gap-3">
                    <span class="theme-cart-soft flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"><i class="bi bi-hdd-network"></i></span>
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.dns_choice.custom') }}</span>
                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.dns_choice.custom_help') }}</span>
                    </span>
                </span>
            </label>
        </div>
        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900/60 {{ $nameserverMode === 'custom' ? '' : 'hidden' }}" data-custom-nameservers>
            <div class="grid sm:grid-cols-2 gap-4">
                @for($i = 0; $i < 4; $i++)
                    <div>@include('shared/input', ['name' => "nameservers[$i]", 'label' => __('provisioning.domain_manager.nameserver', ['number' => $i + 1]), 'value' => $currentNameservers[$i] ?? '', 'attributes' => ['placeholder' => 'ns'.($i + 1).'.example.com']])</div>
                @endfor
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.dns_choice.custom_notice') }}</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-domain-order]').forEach((root) => {
        const tld = root.querySelector('[name="tld"]');
        const custom = root.querySelector('[data-custom-nameservers]');
        const list = root.querySelector('[data-managed-nameservers]');
        const nameservers = JSON.parse(root.dataset.nameservers || '{}');
        const refresh = () => {
            const usesCustomNameservers = root.querySelector('[name="nameserver_mode"]:checked')?.value === 'custom';
            custom.classList.toggle('hidden', !usesCustomNameservers);
            custom.querySelectorAll('input').forEach((input) => input.disabled = !usesCustomNameservers);
            const values = nameservers[tld?.value] || [];
            list.innerHTML = values.length
                ? values.map((server) => `<span class="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300"><strong class="font-mono">${server.hostname}</strong>${server.ipv4 || server.ipv6 ? `<small class="mt-0.5 block font-mono opacity-70">${[server.ipv4, server.ipv6].filter(Boolean).join(' · ')}</small>` : ''}</span>`).join('')
                : `<span class="text-xs text-amber-600">{{ __('provisioning.domain_manager.dns_choice.unavailable') }}</span>`;
        };
        root.querySelectorAll('[name="nameserver_mode"]').forEach((radio) => radio.addEventListener('change', refresh));
        tld?.addEventListener('change', refresh);
        refresh();
    });
});
</script>
