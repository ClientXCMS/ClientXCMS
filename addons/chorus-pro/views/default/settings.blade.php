@extends('admin/layouts/admin')
@section('title', __('chorus-pro::messages.settings.title'))
@section('content')
<div class="container mx-auto max-w-4xl">
    @include('admin/shared/alerts')
    <div class="card">
        <div class="card-heading">
            <div><h1 class="text-xl font-semibold">{{ __('chorus-pro::messages.settings.title') }}</h1><p class="text-sm text-gray-500">{{ __('chorus-pro::messages.settings.description') }}</p></div>
            <form method="post" action="{{ route('admin.chorus-pro.settings.test') }}">@csrf<button class="btn btn-secondary"><i class="bi bi-plug"></i> {{ __('chorus-pro::messages.settings.test') }}</button></form>
        </div>
        <p class="mb-4 {{ $configured ? 'text-green-600' : 'text-amber-600' }}">{{ $configured ? __('chorus-pro::messages.settings.configured') : __('chorus-pro::messages.settings.not_configured') }}</p>
        <form method="post" action="{{ route('admin.chorus-pro.settings.update') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">@csrf @method('PUT')
            <div>@include('admin/shared/checkbox', ['name'=>'chorus_pro_enabled','label'=>__('chorus-pro::messages.settings.enabled'),'value'=>setting('chorus_pro_enabled', false)])</div>
            <div>@include('admin/shared/select', ['name'=>'chorus_pro_environment','label'=>__('chorus-pro::messages.settings.environment'),'options'=>['qualification'=>__('chorus-pro::messages.settings.qualification'),'production'=>__('chorus-pro::messages.settings.production')],'value'=>setting('chorus_pro_environment', 'qualification')])</div>
            <div>@include('admin/shared/input', ['name'=>'chorus_pro_client_id','type'=>'password','label'=>__('chorus-pro::messages.settings.client_id'),'value'=>'','optional'=>filled(setting('chorus_pro_client_id'))])</div>
            <div>@include('admin/shared/input', ['name'=>'chorus_pro_client_secret','type'=>'password','label'=>__('chorus-pro::messages.settings.client_secret'),'value'=>'','optional'=>filled(setting('chorus_pro_client_secret'))])</div>
            <div class="md:col-span-2">@include('admin/shared/input', ['name'=>'chorus_pro_account','type'=>'password','label'=>__('chorus-pro::messages.settings.account'),'value'=>'','optional'=>filled(setting('chorus_pro_account'))])</div>
            <div>@include('admin/shared/input', ['name'=>'chorus_pro_api_url','label'=>__('chorus-pro::messages.settings.api_url'),'value'=>setting('chorus_pro_api_url', 'https://sandbox-api.piste.gouv.fr/cpro')])</div>
            <div>@include('admin/shared/input', ['name'=>'chorus_pro_oauth_url','label'=>__('chorus-pro::messages.settings.oauth_url'),'value'=>setting('chorus_pro_oauth_url', 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token')])</div>
            <div>@include('admin/shared/input', ['name'=>'chorus_pro_timeout','type'=>'number','label'=>__('chorus-pro::messages.settings.timeout'),'value'=>setting('chorus_pro_timeout', 30)])</div>
            <div class="md:col-span-2"><button class="btn btn-primary">{{ __('global.save') }}</button></div>
        </form>
    </div>
</div>
@endsection
