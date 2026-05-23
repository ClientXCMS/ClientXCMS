@extends('errors._layout')

@section('status_code', '403')
@section('title', __('v216::errors.403.title'))
@section('heading', __('v216::errors.403.heading'))
@section('description', __('v216::errors.403.description'))

@section('illustration')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M5.6 5.6 18.4 18.4"/>
    </svg>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
    @if(\Illuminate\Support\Facades\Route::has('front.support.create'))
        <a class="btn btn-secondary" href="{{ route('front.support.create') }}">{{ __('v216::errors.common.support') }}</a>
    @endif
