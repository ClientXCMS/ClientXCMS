@php
    $domain = old('domain', request('domain', $data['domain'] ?? ''));
    $operation = old('operation', request('operation', $data['operation'] ?? 'register'));
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

<div class="space-y-6 text-slate-700 dark:text-slate-300" data-domain-order>
    <input type="hidden" name="operation" value="{{ $operation === 'transfer' ? 'transfer' : 'register' }}">
    @if($operation === 'transfer')
        <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">{{ __('provisioning.domain_manager.search.transfer_help') }}</div>
        <div>@include('shared/input', ['name' => 'auth_code', 'label' => __('provisioning.domain_manager.search.auth_code'), 'value' => old('auth_code', $data['auth_code'] ?? '')])</div>
    @endif
    <div>@include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => $domain])</div>

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
                        <span class="mt-3 flex flex-wrap gap-2"></span>
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
        const custom = root.querySelector('[data-custom-nameservers]');
        const refresh = () => {
            const usesCustomNameservers = root.querySelector('[name="nameserver_mode"]:checked')?.value === 'custom';
            custom.classList.toggle('hidden', !usesCustomNameservers);
            custom.querySelectorAll('input').forEach((input) => input.disabled = !usesCustomNameservers);
        };
        root.querySelectorAll('[name="nameserver_mode"]').forEach((radio) => radio.addEventListener('change', refresh));
        refresh();
    });
});
</script>
