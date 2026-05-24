{{-- Fallback stub. The themed view ships in the customer-storefront addon. --}}
@extends('layouts/auth')
@section('content')
<div class="p-6">
    <h1 class="text-xl font-bold">{{ __('store.domains.title') }}</h1>
    @isset($query)<p class="mt-2 text-sm">{{ $query }}</p>@endisset
</div>
@endsection
