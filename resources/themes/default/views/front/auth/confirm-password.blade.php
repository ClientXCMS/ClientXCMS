@extends('layouts/auth')
@section('title', __('auth.passkeys.confirm_title'))
@section('content')
<div class="p-4 sm:p-7">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('auth.passkeys.confirm_title') }}</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.passkeys.confirm_description') }}</p>
    @include('shared.alerts')
    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-5 grid gap-y-4">
        @csrf
        @include('shared.input', ['name' => 'password', 'type' => 'password', 'label' => trans('global.password')])
        <button type="submit" class="btn-primary w-full">{{ __('auth.passkeys.confirm') }}</button>
    </form>
    @if(setting('passkeys_enabled', false) && auth('web')->user()->hasPasskeysEnabled())
        @include('shared.auth.passkeys', ['confirmation' => true])
    @endif
</div>
@endsection
