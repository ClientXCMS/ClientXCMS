@if($results->isEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">{{ __('provisioning.domain_manager.search.no_available') }}</div>
@else
    <section aria-labelledby="domain-results-title">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><h2 id="domain-results-title" class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.search.results') }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.alternatives_help') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $results->count() }} {{ __('provisioning.domain_manager.tld') }}</span></div>
        <div class="space-y-3">
            @foreach($results as $result)
                @include('front.store.domains.card', ['featured' => $loop->first])
            @endforeach
        </div>
    </section>
@endif
