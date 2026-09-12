@php($displayNameservers = old('nameservers', $nameservers ?: ['', '']))
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:p-6 text-slate-700 dark:text-slate-300" data-nameservers-manager>
    <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"><i class="bi bi-hdd-network"></i></span>
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.nameservers') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.nameservers_editor.help') }}</p>
        </div>
    </div>
    @if(!empty($recommendedNameservers))
    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900/40 dark:bg-blue-900/20">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.nameservers_editor.recommended') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.nameservers_editor.recommended_help') }}</p>
            </div><button type="button" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 dark:border-blue-800 dark:bg-slate-800 dark:text-blue-300" data-use-recommended>{{ __('provisioning.domain_manager.nameservers_editor.use_recommended') }}</button>
        </div>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach($recommendedNameservers as $index => $nameserver)<span class="rounded-md bg-white px-3 py-2 text-xs text-slate-600 shadow-sm dark:bg-slate-800 dark:text-slate-300" data-recommended-value><strong class="font-mono">{{ $nameserver }}</strong>@if(!empty($recommendedNameserverIps[$index]['ipv4']) || !empty($recommendedNameserverIps[$index]['ipv6']))<small class="mt-1 block font-mono text-slate-400">{{ collect([$recommendedNameserverIps[$index]['ipv4'] ?? null, $recommendedNameserverIps[$index]['ipv6'] ?? null])->filter()->join(' · ') }}</small>@endif</span>@endforeach</div>
    </div>
    @endif
    <form method="POST" action="{{ route('front.services.domains.nameservers', ['service' => $service]) }}" class="mt-6">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2" data-nameserver-list>
            @foreach($displayNameservers as $i => $nameserver)
            <div class="relative" data-nameserver-row>@include('shared/input', ['name' => "nameservers[$i]", 'label' => __('provisioning.domain_manager.nameserver', ['number' => $i + 1]), 'value' => $nameserver, 'attributes' => ['placeholder' => 'ns'.($i + 1).'.example.com']])</div>
            @endforeach
        </div>
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300" data-add-nameserver><i class="bi bi-plus-lg"></i>{{ __('provisioning.domain_manager.nameservers_editor.add') }}</button><button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"><i class="bi bi-check2"></i>{{ __('global.save') }}</button></div>
    </form>
</section>
<template data-nameserver-template>
    <div class="relative" data-nameserver-row><label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-300"></label>
        <div class="flex gap-2"><input class="input-text" type="text"><button type="button" class="rounded-lg px-3 text-slate-400 hover:bg-red-50 hover:text-red-600" data-remove-nameserver aria-label="{{ __('global.delete') }}"><i class="bi bi-x-lg"></i></button></div>
    </div>
</template>
<script>
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-nameservers-manager]').forEach((root) => {
        const list = root.querySelector('[data-nameserver-list]'),
            template = root.querySelector('[data-nameserver-template]');
        const renumber = () => list.querySelectorAll('[data-nameserver-row]').forEach((row, index) => {
            const input = row.querySelector('input');
            input.name = `nameservers[${index}]`;
            row.querySelector('label').textContent = `{{ __('provisioning.domain_manager.nameservers_editor.server') }} ${index + 1}`;
        });
        root.querySelector('[data-add-nameserver]').addEventListener('click', () => {
            if (list.children.length >= 8) return;
            list.append(template.content.cloneNode(true));
            renumber();
        });
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-nameserver]');
            if (button && list.children.length > 2) {
                button.closest('[data-nameserver-row]').remove();
                renumber();
            }
        });
        root.querySelector('[data-use-recommended]')?.addEventListener('click', () => {
            const values = [...root.querySelectorAll('[data-recommended-value] strong')].map((el) => el.textContent.trim());
            while (list.children.length < values.length) list.append(template.content.cloneNode(true));
            while (list.children.length > Math.max(2, values.length)) list.lastElementChild.remove();
            renumber();
            values.forEach((value, index) => list.querySelectorAll('input')[index].value = value);
        });
    }));
</script>
