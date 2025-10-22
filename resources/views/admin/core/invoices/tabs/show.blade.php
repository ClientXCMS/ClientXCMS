<div class="flex justify-between">
    <div>
        <img class="mx-auto h-12 w-auto mt-4" src="{{ setting('app_logo_text') }}" alt="{{ setting('app_name') }}">
    </div>
    <div class="text-end">
        <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-200">{{ __('global.invoice') }} #</h2>
        <span class="mt-1 block text-gray-500">{{ $invoice->identifier() }}</span>

        <address class="mt-4 not-italic text-gray-800 dark:text-gray-200">
            {!! nl2br(setting('app_address')) !!}
        </address>
    </div>
</div>

<div class="mt-8 grid sm:grid-cols-2 gap-3">
    @if ($customer)
    <a href="{{ route('admin.customers.show', ['customer' => $customer]) }}" target="_blank">
    @endif
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.billto', ['name' => $address[0]]) }}</h3>
        <address class="mt-2 not-italic text-gray-500">
            @foreach ($address as $i => $line)
                @if ($i == 0)
                    @continue
                @endif
                {{ $line }}<br />
            @endforeach
        </address>
    @if ($customer)
    </a>
    @endif

    <div class="space-y-2">
        <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
            <dl class="grid sm:grid-cols-5 gap-x-3">
                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.invoice_date') }}:</dt>
                <dd class="col-span-2 text-gray-500">{{ $invoice->created_at->format('d/m/y H:i') }}</dd>
            </dl>

            <dl class="grid sm:grid-cols-5 gap-x-3">
                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('client.invoices.due_date') }}:</dt>
                <dd class="col-span-2 text-gray-500">{{ $invoice->due_date->format('d/m/y H:i') }}</dd>
            </dl>
            <dl class="grid sm:grid-cols-5 gap-x-3">
                <dt class="col-span-3 font-semibold text-gray-800 dark:text-gray-200">{{ __('global.status') }}:</dt>
                <dd class="col-span-2 text-gray-500"><x-badge-state state="{{ $invoice->status }}"></x-badge-state></dd>
            </dl>
        </div>
    </div>
</div>

<div class="mt-6">
    <div class="border border-gray-200 p-4 rounded-lg space-y-4 dark:border-gray-700">
        <div class="hidden sm:grid sm:grid-cols-6">
            <div class="sm:col-span-2 text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.itemname')  }}</div>
            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.qty') }}</div>
            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('store.unit_price') }}</div>
            <div class="text-start text-xs font-medium text-gray-500 uppercase">{{ __('store.setup_price') }}</div>
            <div class="text-center text-xs font-medium text-gray-500 uppercase">{{ __('store.price') }}</div>
        </div>

        @if ($invoice->items->isEmpty())
        <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
            <td colspan="9" class="px-6 py-4 whitespace-nowrap text-center">
                <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                    <p class="text-sm text-gray-800 dark:text-gray-400">
                        {{ __('global.no_results') }}
                    </p>
                </div>
            </td>
        </tr>
        @endif
        @foreach ($invoice->items as $item)
        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

        <div class="grid grid-cols-1 sm:grid-cols-6 gap-2">
            <div class="sm:col-span-2 sm:flex">
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.itemname') }}</h5>


                @if ($invoice->isDraft())

                <form method="POST" class="flex" action="{{ route($routePath . '.deleteitem', ['invoice_item' => $item, 'invoice' => $invoice]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="text-danger mx-2" type="submit">
                        <i class="bi bi-trash"></i>
                        <span class="sr-only">{{ __('global.delete') }}</span>
                    </button>

                    <button type="button" id="btn-edit-{{ $item->id }}" class="text-warning mx-2" data-hs-overlay="#edititem-{{ $item->id }}">
                        <i class="bi bi-pencil mr-2"></i>
                        <span class="sr-only">{{ __('global.edit') }}</span>
                    </button>
                </form>
                @endif
                <div>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $item->name }}</p>
                    @if ($item->canDisplayDescription())
                    <span class="font-medium text-gray-500 dark:text-gray-400">{!! nl2br($item->description) !!}</span>
                    @endif
                    @if ($item->getDiscount(false) != null)
                    <span class="font-medium text-gray-400 text-start">{{ $item->getDiscountLabel() }}</span>
                    @endif
                </div>
            </div>
            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('client.invoices.qty') }}</h5>
                <p class="text-gray-800 dark:text-gray-200">{{ $item->quantity }}</p>
            </div>
            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.unit_price') }}</h5>
                <div class="block">
                    <p class="text-gray-800 dark:text-gray-200 text-start">{{ formatted_price($item->unit_price_ht, $invoice->currency) }}</p>
                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_price != 0)
                    <p class="font-medium text-gray-400 text-start">-{{ formatted_price($item->getDiscount()->sub_price, $invoice->currency) }}</p>
                    @endif
                </div>
            </div>
            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.setup_price') }}</h5>
                <div class="block">
                    <p class="text-gray-800 dark:text-gray-200 text-start">{{ formatted_price($item->unit_setup_ht, $invoice->currency) }}</p>
                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0)
                    <p class="font-medium text-gray-400 text-start">-{{ formatted_price($item->getDiscount()->sub_setup, $invoice->currency) }}</p>
                    @endif
                </div>
            </div>

            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.price') }}</h5>
                <div class="block">
                    <p class="text-gray-800 dark:text-gray-200 md:text-end sm:text-start">{{ formatted_price($item->price(), $invoice->currency) }}</p>
                    @if ($item->getDiscount() != null && $item->getDiscount(true)->sub_setup != 0 || $item->getDiscount()->sub_price != 0)
                    <p class="font-medium text-gray-400 md:text-end sm:text-start">-{{ formatted_price($item->getDiscount()->sub_price + $item->getDiscount()->sub_setup, $invoice->currency) }}</p>
                    @endif
                </div>
            </div>

        </div>

        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
            <div class="col-span-5 hidden sm:grid">
                <p class="sm:text-end font-semibold text-gray-800 dark:text-gray-200">{{ __('store.transaction_fee') }}</p>
            </div>

            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.transaction_fee') }}</h5>

                <p class="text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ formatted_price($invoice->fees, $invoice->currency) }}</p>
            </div>

        </div>
        <div class="hidden sm:block border-b border-gray-200 dark:border-gray-700"></div>

        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
            <div class="col-span-5 hidden sm:grid">
                <p class="font-semibold text-gray-800 dark:text-gray-200 sm:text-end text-start">{{ __('store.total') }}</p>
            </div>

            <div>
                <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">{{ __('store.total') }}</h5>

                <p class="text-gray-800 dark:text-gray-200 sm:text-end sm:text-end text-start">{{ formatted_price($invoice->total, $invoice->currency) }}</p>
            </div>

        </div>
        @endforeach
    </div>
</div>

@if ($invoice->isDraft() && staff_has_permission('admin.manage_invoices'))

<div class="mt-8 grid sm:grid-cols-2 gap-3">
    <div>
    </div>

    <div class="space-y-2">
        <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
            @include('admin/shared/search-select', ['name' => 'product', 'label' => __($translatePrefix . '.draft.add'), 'options' => $products, 'value' => 1])
            <button class="btn btn-primary mt-2" id="add-item-btn" data-fetch="{{ route($routePath . '.config', ['invoice' => $invoice]) }}">{{ __('global.add') }}</button>
        </div>
    </div>
</div>
@endif

@if (!$invoice->isDraft())
    @if ($invoice->external_id != null)
    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="overflow-hidden">
                    <div class="border border-gray-200 p-2 rounded-lg space-y-2 dark:border-gray-700 mt-3">

                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                            {{ __('client.invoices.paymethod') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                            {{ __('client.invoices.paid_date') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">
                                            {{ __($translatePrefix .'.show.external_id') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $invoice->gateway != null ? $invoice->gateway->name : $invoice->paymethod }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/y H:i') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                            {{ $invoice->external_id }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <a class="btn-primary btn w-full mt-4" href="{{ route($routePath . '.pdf', ['invoice' => $invoice]) }}">
        {{ __('client.invoices.download') }}
    </a>
@endif
