@if($results->isEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">{{ __('provisioning.domain_manager.search.no_available') }}</div>
@else
    <section aria-labelledby="domain-results-title">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><h2 id="domain-results-title" class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('provisioning.domain_manager.search.results') }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.alternatives_help') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $results->count() }} {{ __('provisioning.domain_manager.tld') }}</span></div>
        <div class="space-y-3">
            @foreach($results as $result)
                @php
                    $availability = $result['availability'];
                    $canRegister = $operation !== 'transfer' && $availability->checked && $availability->available && !empty($result['prices']);
                    $canTransfer = $result['can_transfer'] && ($operation === 'transfer' || ($availability->checked && !$availability->available));
                    $price = $canRegister ? $result['prices'][0] : ($canTransfer ? $result['transfer_prices'][0] : null);
                @endphp
                <article class="flex flex-col gap-4 rounded-2xl border {{ $loop->first ? 'border-indigo-200 bg-indigo-50/40 dark:border-indigo-800 dark:bg-indigo-950/20' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800' }} p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="break-all text-lg font-bold text-slate-900 dark:text-white sm:text-xl">{{ $result['domain'] }}</h3>
                        @if(!$availability->checked)
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.check_failed') }}</p>
                        @elseif($availability->available)
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.available') }}</p>
                        @else
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-rose-700 dark:text-rose-400"><i class="bi bi-x-circle-fill" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.unavailable') }}</p>
                        @endif
                        @if($canTransfer)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.transfer_help') }}</p>@endif
                    </div>
                    @if($price && $product)
                        <a class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl {{ $canTransfer ? 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-white' : 'bg-indigo-600 text-white hover:bg-indigo-700' }} px-5 py-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" href="{{ route('front.store.basket.config', ['product' => $product, 'domain' => $result['domain'], 'tld' => $result['tld']->extension, 'billing' => $price->recurring, 'operation' => $canTransfer ? 'transfer' : 'register']) }}">{{ $canTransfer ? __('provisioning.domain_manager.search.transfer_cta') : __('provisioning.domain_manager.search.choose') }} · {{ formatted_price($price->firstPayment(), $price->currency) }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
