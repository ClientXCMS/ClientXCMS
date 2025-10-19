<form method="POST" action="{{ route($routePath . '.update', ['invoice' => $invoice]) }}">
    @method('PUT')
    @csrf

    <div class="grid sm:grid-cols-3 gap-2">
        @php($paymentDetailsUrl = $invoice->gateway ? $invoice->gateway->paymentType()->getPaymentDetailsUrl($invoice) : null)
        @if ($paymentDetailsUrl)
        <div>
            <label for="invoice_url" class="block text-sm font-medium mt-2">{{ __($translatePrefix.'show.external_id') }}</label>
            <div class="flex rounded-lg shadow-sm mt-2">
                <input type="text" readonly class="input-text" value="{{ $invoice->external_id }}">
                <a href="{{ $paymentDetailsUrl }}" target="_blank" class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                    <i class="bi bi-arrows-angle-expand"></i>
                </a>
            </div>
        </div>
        @else
        <div>
            @include('admin/shared/input', ['name' => 'external_id', 'label' => __($translatePrefix .'.show.external_id'), 'value' => $invoice->external_id])
        </div>
        @endif
        <div>
            @include('admin/shared/select', ['name' => 'status', 'label' => __('global.status'), 'options' => $invoice::getStatuses(), 'value' => $invoice->status])
        </div>
        <div>
            @include('admin/shared/select', ['name' => 'paymethod', 'label' => __('client.invoices.paymethod'), 'options' => $gateways, 'value' => $invoice->paymethod])
        </div>
    </div>

    @include('admin/shared/textarea', ['name' => 'notes', 'label' => __($translatePrefix .'.show.notes'), 'value' => $invoice->notes])

    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-2">
        <div>
            @include('admin/shared/input', ['name' => 'payment_method_id', 'label' => __('client.payment-methods.payment_method_used'), 'value' => $invoice->payment_method_id, 'help' => __('client.payment-methods.payment_method_used_help')])
        </div>
        <div>
            @include('admin/shared/input', ['name' => 'balance', 'label' => __('client.invoices.balance.title'), 'value' => $invoice->balance, 'type' => 'number', 'step' => 'any'])
        </div>
        <div>
            @include('admin/shared/flatpickr', ['name' => 'paid_at', 'label' => __('client.invoices.paid_date'), 'value' => $invoice->paid_at])
        </div>
        <div>
            @include('admin/shared/flatpickr', ['name' => 'due_date', 'label' => __('client.invoices.due_date'), 'value' => $invoice->due_date])
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-2 mt-2">
        <div>
            @include('admin/shared/input', ['name' => 'fees', 'label' => __('store.transaction_fee'), 'value' => $invoice->fees, 'type' => 'number', 'step' => 'any'])
        </div>
        <div>
            <label for="tax" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ __('store.vat') }} / {{ __('global.currency') }}</label>
            <div class="relative mt-2">
                <input type="text" id="tax" name="tax" class="py-3 px-4 ps-9 pe-20 input-text" placeholder="0.00" value="{{ old('tax', $invoice->tax) }}">
                <div class="absolute inset-y-0 end-0 flex items-center text-gray-500 pe-px">
                    <label for="currency" class="sr-only">{{ __('global.currency') }}</label>
                    <select id="currency" name="currency" class="store w-full border-transparent rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700">
                        @foreach(currencies() as $currency)
                        <option value="{{ $currency['code'] }}" @if($currency['code']==$invoice->currency) selected @endif>{{ $currency['code'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @error('tax')<span class="mt-2 text-sm text-red-500">{{ $message }}</span>@enderror
            @error('currency')<span class="mt-2 text-sm text-red-500">{{ $message }}</span>@enderror
        </div>
    </div>
    <button class="btn btn-primary mt-4 w-full">{{ __('global.save') }}</button>
</form>
