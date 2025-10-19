<div class="flex items-start justify-between mb-3">
    <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
            {{ __($translatePrefix .'.show.fulfillment.title') }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __($translatePrefix .'.show.fulfillment.subheading') }}
        </p>
    </div>
    @if ($invoice->items->isNotEmpty())
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
        {{ trans_choice($translatePrefix .'.show.fulfillment.items_count', $invoice->items->count(), ['count' => $invoice->items->count()]) }}
    </span>
    @endif
</div>
<div class="hidden md:block overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('client.invoices.itemname') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('client.invoices.qty') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('store.price') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('global.status') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('global.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-slate-900">
            @forelse($invoice->items as $item)
            <tr>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $item->name }}</div>
                    @if ($item->canDisplayDescription())
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{!! nl2br($item->description) !!}</div>
                    @endif
                    @if (!empty($item->data))
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('provisioning.admin.services.data.orderdata') }}
                        </summary>
                        <pre class="mt-2 overflow-x-auto rounded bg-gray-100 dark:bg-gray-800 p-2 text-xs text-gray-700 dark:text-gray-300">
                        {{ json_encode($item->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                        </pre>
                    </details>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                    {{ $item->quantity }}
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                    {{ formatted_price($item->price(), $invoice->currency) }}
                </td>
                <td class="px-4 py-3">
                    @if ($item->cancelled_at)
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 text-red-700 ring-1 ring-red-200 px-2 py-1 text-xs font-medium dark:bg-red-900/20 dark:text-red-300 dark:ring-red-800">
                        {{ __($translatePrefix .'.cancelled_at', ['date' => $item->cancelled_at->format('d/m/Y H:i')]) }}
                    </span>
                    @elseif ($item->delivered_at)
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 ring-1 ring-green-200 px-2 py-1 text-xs font-medium dark:bg-green-900/20 dark:text-green-300 dark:ring-green-800">
                        {{ __($translatePrefix .'.delivered_at', ['date' => $item->delivered_at->format('d/m/Y H:i')]) }}
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 px-2 py-1 text-xs font-medium dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800">
                        {{ __('global.states.pending') }}
                    </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        @if (is_null($item->delivered_at))
                        <form method="POST" action="{{ route($routePath . '.deliver', ['invoice_item' => $item, 'invoice' => $invoice]) }}">
                            @csrf
                            <button class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold bg-primary text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <i class="bi bi-truck me-1"></i> {{ __($translatePrefix .'.deliver') }}
                            </button>
                        </form>
                        @endif

                        @if (is_null($item->cancelled_at))
                        <form method="POST" action="{{ route($routePath . '.cancelitem', ['invoice_item' => $item, 'invoice' => $invoice]) }}"
                            class="confirmation-popup" data-text="{{ __($translatePrefix .'.show.fulfillment.confirmation.cancel_title') }}" data-cancel-button-text="{{ __($translatePrefix .'.show.fulfillment.confirmation.cancel') }}" data-confirm-button-text="{{ __($translatePrefix .'.show.fulfillment.confirmation.confirm') }}">
                            @csrf
                            <button class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="bi bi-x-circle me-1"></i> {{ __('global.cancel') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('global.no_results') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="md:hidden space-y-3">
        @forelse($invoice->items as $item)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-slate-900">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item->name }}</div>
                    @if ($item->canDisplayDescription())
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{!! nl2br($item->description) !!}</div>
                    @endif
                </div>
                <div>
                    @if ($item->cancelled_at)
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 text-red-700 ring-1 ring-red-200 px-2 py-1 text-[11px] font-medium dark:bg-red-900/20 dark:text-red-300 dark:ring-red-800">
                        {{ __('global.states.cancelled') }}
                    </span>
                    @elseif ($item->delivered_at)
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 ring-1 ring-green-200 px-2 py-1 text-[11px] font-medium dark:bg-green-900/20 dark:text-green-300 dark:ring-green-800">
                        {{ __('global.states.delivered') }}
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 px-2 py-1 text-[11px] font-medium dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800">
                        {{ __('global.states.pending') }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                <div class="text-gray-500">{{ __('client.invoices.qty') }}</div>
                <div class="text-gray-500">{{ __('store.price') }}</div>
                <div class="text-gray-500">{{ __('global.status') }}</div>

                <div class="text-gray-800 dark:text-gray-200">{{ $item->quantity }}</div>
                <div class="text-gray-800 dark:text-gray-200">{{ formatted_price($item->price(), $invoice->currency) }}</div>
                <div>
                    @if ($item->cancelled_at)
                    <span class="text-red-600 dark:text-red-400">{{ __('global.states.cancelled') }}</span>
                    @elseif ($item->delivered_at)
                    <span class="text-green-600 dark:text-green-400">{{ __('global.states.delivered') }}</span>
                    @else
                    <span class="text-amber-600 dark:text-amber-300">{{ __('global.states.pending') }}</span>
                    @endif
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2">
                @if (is_null($item->delivered_at))
                <form method="POST" action="{{ route($routePath . '.deliver', ['invoice_item' => $item, 'invoice' => $invoice]) }}">
                    @csrf
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary text-white px-3 py-2 text-xs font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <i class="bi bi-truck"></i> {{ __($translatePrefix .'.deliver') }}
                    </button>
                </form>
                @endif

                @if (is_null($item->cancelled_at) && !is_null($item->delivered_at))
                <form method="POST" action="{{ route($routePath . '.cancelitem', ['invoice_item' => $item, 'invoice' => $invoice]) }}"
                    onsubmit="return confirm('{{ __($translatePrefix .'.fulfillment.confirm_cancel') }}');">
                    @csrf
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 text-white px-3 py-2 text-xs font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="bi bi-x-circle"></i> {{ __('global.cancel') }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('global.no_results') }}
        </div>
        @endforelse
    </div>
</div>
