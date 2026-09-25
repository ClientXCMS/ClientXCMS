                @php
                    $availability = $result['availability'];
                    $canRegister = $operation !== 'transfer' && $availability?->checked && $availability?->available && !empty($result['prices']);
                    $canTransfer = $result['can_transfer'] && ($operation === 'transfer' || ($availability?->checked && !$availability?->available));
                    $price = $canRegister ? $result['prices'][0] : ($canTransfer ? $result['transfer_prices'][0] : null);
                @endphp
                <article data-domain-card="{{ $result['domain'] }}" class="flex flex-col gap-4 rounded-2xl border {{ $featured ? 'border-primary/40 bg-primary/10 dark:border-primary/60 dark:bg-primary/20' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800' }} p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="break-all text-lg font-bold text-slate-900 dark:text-white sm:text-xl">{{ $result['domain'] }}</h3>
                        @if($availability === null)
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400" role="status"><i class="bi bi-arrow-repeat animate-spin" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.checking') }}</p>
                        @elseif(!$availability?->checked)
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.check_failed') }}</p>
                        @elseif($availability?->available)
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.available') }}</p>
                        @else
                            <p class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-rose-700 dark:text-rose-400"><i class="bi bi-x-circle-fill" aria-hidden="true"></i>{{ __('provisioning.domain_manager.search.unavailable') }}</p>
                        @endif
                        @if($canTransfer)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.search.transfer_help') }}</p>@endif
                    </div>
                    @if($price && $product)
                        <a class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl {{ $canTransfer ? 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-white' : 'bg-primary text-white hover:bg-primary-dark' }} px-5 py-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" href="{{ route('front.store.basket.config', ['product' => $product, 'domain' => $result['domain'], 'tld' => $result['tld']->extension, 'billing' => $price->recurring, 'operation' => $canTransfer ? 'transfer' : 'register']) }}">{{ $canTransfer ? __('provisioning.domain_manager.search.transfer_cta') : __('provisioning.domain_manager.search.choose') }} · {{ formatted_price($price->firstPayment(), $price->currency) }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </article>
