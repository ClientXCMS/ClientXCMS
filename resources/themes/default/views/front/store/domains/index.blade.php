@extends('layouts/front')
@section('title', __('provisioning.domain_manager.search.title'))
@section('content')
<div class="{{ theme_metadata('layout_classes', 'max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto') }}">
    @include('shared.alerts')
    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-primary-light px-6 py-9 shadow-sm sm:px-10 sm:py-12 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-primary/20">
        <div class="pointer-events-none absolute -right-16 -top-28 h-72 w-72 rounded-full bg-primary/10 blur-3xl dark:bg-primary/20" aria-hidden="true"></div>
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
                    <button type="submit" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-60"><i class="bi bi-search" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.submit') }}</button>
                </div>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.alternatives_help') }}</p>
            </form>
        @endif
    </div>
    @if($product)
        <div id="domain-search-loading" role="status" class="mt-8 hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-4"><span class="inline-flex h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-primary-light border-t-primary dark:border-slate-700 dark:border-t-primary" aria-hidden="true"></span><p class="font-medium text-slate-800 dark:text-white">{{ __('provisioning.domain_manager.search.loading') }}</p></div>
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
    const cachePrefix = 'domain-search:v1:';
    const cacheScope = @json(hash('sha256', session()->getId()));
    const cacheContext = @json([currency(), app()->getLocale()]);
    const cacheLifetime = 5 * 60 * 1000;
    const cacheKey = (data) => cachePrefix + JSON.stringify([
        cacheScope, cacheContext, String(data.get('operation') || 'register'),
        String(data.get('domain') || '').toLowerCase(),
    ]);
    const cachedResults = (key) => {
        try {
            const cached = JSON.parse(localStorage.getItem(key));
            if (cached && cached.expires > Date.now() && typeof cached.html === 'string') return cached.html;
            localStorage.removeItem(key);
        } catch (_) { /* Storage may be disabled by the browser. */ }
        return null;
    };
    const saveResults = (key, html) => {
        try {
            localStorage.setItem(key, JSON.stringify({html, expires: Date.now() + cacheLifetime}));
        } catch (_) { /* The search remains usable without browser storage. */ }
    };
    let currentSearch = null;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        currentSearch?.abort();
        const controller = new AbortController();
        currentSearch = controller;
        const searchData = new FormData(form);
        const key = cacheKey(searchData);
        const cached = cachedResults(key);
        if (cached !== null) {
            loading.classList.add('hidden');
            output.innerHTML = cached;
            currentSearch = null;
            return;
        }
        loading.classList.remove('hidden');
        output.replaceChildren();
        try {
            const response = await fetch(form.action, {method: 'POST', body: searchData, signal: controller.signal, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const body = await response.json();
            if (!response.ok) throw new Error(Object.values(body.errors || {}).flat().join(' ') || body.message);
            if (currentSearch !== controller) return;
            output.innerHTML = body.html;
            loading.classList.add('hidden');

            const batches = body.batches || [];
            let nextBatch = 0;
            let hadFailures = false;
            const updateCard = (domain, html) => {
                const card = Array.from(output.querySelectorAll('[data-domain-card]')).find((element) => element.dataset.domainCard === domain);
                if (!card) return;
                const template = document.createElement('template');
                template.innerHTML = html.trim();
                if (template.content.firstElementChild) card.replaceWith(template.content.firstElementChild);
            };
            const failBatch = (batch) => {
                hadFailures = true;
                for (const domain of batch.domains) {
                    const card = Array.from(output.querySelectorAll('[data-domain-card]')).find((element) => element.dataset.domainCard === domain);
                    if (!card) continue;
                    const status = card.querySelector('[role="status"]');
                    if (!status) continue;
                    status.className = 'mt-2 inline-flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400';
                    status.textContent = @json(__('provisioning.domain_manager.search.check_failed'));
                    status.removeAttribute('role');
                }
            };
            const worker = async () => {
                while (nextBatch < batches.length && !controller.signal.aborted) {
                    const batch = batches[nextBatch++];
                    try {
                        const data = new FormData(form);
                        data.set('domain', body.domain);
                        data.set('batch', batch.id);
                        const result = await fetch(@json(route('front.store.domains.check')), {method: 'POST', body: data, signal: controller.signal, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                        if (!result.ok) throw new Error('Domain check failed');
                        const checked = await result.json();
                        if (currentSearch !== controller) return;
                        if (checked.failed?.length) hadFailures = true;
                        for (const domain of batch.domains) {
                            if (checked.cards?.[domain]) updateCard(domain, checked.cards[domain]);
                            else failBatch({domains: [domain]});
                        }
                    } catch (error) {
                        if (controller.signal.aborted) return;
                        failBatch(batch);
                    }
                }
            };
            await Promise.all(Array.from({length: Math.min(3, batches.length)}, worker));
            if (currentSearch === controller && !hadFailures) saveResults(key, output.innerHTML);
        } catch (error) {
            if (controller.signal.aborted || currentSearch !== controller) return;
            const message = document.createElement('p');
            message.className = 'rounded-xl border border-red-200 bg-red-50 p-5 text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200';
            message.textContent = error.message || @json(__('provisioning.domain_manager.search.check_failed'));
            output.append(message);
        } finally {
            if (currentSearch === controller) {
                loading.classList.add('hidden');
                currentSearch = null;
            }
        }
    });
});
</script>
@endsection
