<form method="POST" action="{{ route($routePath . '.update', ['invoice' => $invoice]) }}">
    @method("PUT")
    <input type="hidden" name="status" value="{{ $invoice->status }}">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($invoice->getBillingAddressArray() as $k => $v)
        <div>
            @if ($k == 'billing_details')
                @include('admin/shared/textarea', ['name' => 'billing_address[' . $k . ']', 'label' => __('global.' . $k), 'value' => $v])
            @elseif ($k == 'country')
                @include('admin/shared/select', ['name' => 'billing_address[' . $k . ']', 'label' => __('global.' . $k), 'options' => $countries, 'value' => $v])
            @else
                @include('admin/shared/input', ['name' => 'billing_address[' . $k . ']', 'label' => __('global.' . ($k == 'zipcode' ? 'zip' : $k)), 'value' => $v])
            @endif
        </div>
        @endforeach
    </div>
    <button class="btn btn-primary mt-4 w-full">{{ __('global.save') }}</button>
</form>
