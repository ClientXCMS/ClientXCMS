@extends('layouts/front')
@section('title', __('provisioning.domain_manager.search.title'))
@section('content')
<div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
    @include('shared.alerts')
    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-indigo-50 px-6 py-9 shadow-sm sm:px-10 sm:py-12 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/40">
        <div class="pointer-events-none absolute -right-16 -top-28 h-72 w-72 rounded-full bg-indigo-100/70 blur-3xl dark:bg-indigo-900/20" aria-hidden="true"></div>
        <div class="relative max-w-3xl">
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ __('provisioning.domain_manager.search.title') }}</h1>
            <p class="mt-3 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">{{ __('provisioning.domain_manager.search.subtitle') }}</p>
        </div>
        @if($product === null)
            <div class="relative mt-7 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">{{ __('provisioning.domain_manager.search.no_product') }}</div>
        @else
            <form id="domain-search-form" method="POST" action="{{ route('front.store.domains.search') }}" class="relative mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 dark:border-slate-700 dark:bg-slate-800">
                @csrf
                <input type="hidden" name="operation" value="register">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1 text-slate-800 dark:text-slate-200">@include('shared/input', ['name' => 'domain', 'label' => __('provisioning.domain_manager.domain'), 'value' => old('domain', $query), 'placeholder' => __('provisioning.domain_manager.search.placeholder')])</div>
                    <button type="submit" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-60"><i class="bi bi-search" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.submit') }}</button>
                </div>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.alternatives_help') }}</p>
            </form>
        @endif
    </div>
    @if($product)
        <div id="domain-search-loading" role="status" class="mt-8 hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4"><span class="inline-flex h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600 dark:border-slate-700 dark:border-t-indigo-400" aria-hidden="true"></span><p class="font-medium text-slate-800 dark:text-white">{{ __('provisioning.domain_manager.search.loading') }}</p></div>
        </div>
        <div id="domain-search-results" aria-live="polite" class="mt-8">@if($results->isNotEmpty()) @include('front.store.domains.results') @endif</div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('domain-search-form');
    if (!form) return;
    const output = document.getElementById('domain-search-results');
    const loading = document.getElementById('domain-search-loading');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        loading.classList.remove('hidden');
        output.replaceChildren();
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const body = await response.json();
            if (!response.ok) throw new Error(Object.values(body.errors || {}).flat().join(' ') || body.message);
            output.innerHTML = body.html;
        } catch (error) {
            const message = document.createElement('p');
            message.className = 'rounded-xl border border-red-200 bg-red-50 p-5 text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200';
            message.textContent = error.message || @json(__('provisioning.domain_manager.search.check_failed'));
            output.append(message);
        } finally {
            loading.classList.add('hidden');
            submit.disabled = false;
        }
    });
});
</script>
@endsection
