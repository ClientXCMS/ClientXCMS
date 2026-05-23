@extends('errors._layout')

@section('status_code', '404')
@section('title', __('v216::errors.404.title'))
@section('heading', __('v216::errors.404.heading'))
@section('description', __('v216::errors.404.description'))

@section('illustration')
    {{-- Compass-like 404 icon — works in light/dark via currentColor --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M14.5 9.5 11 11l-1.5 3.5L13 13l1.5-3.5Z"/>
    </svg>
@endsection

@section('actions')
    @php
        $isAdminArea = request()->is('admin*');
    @endphp
    @if($isAdminArea && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
        <a class="btn btn-primary" href="{{ route('admin.dashboard') }}">{{ __('v216::errors.common.dashboard') }}</a>
    @else
        <a class="btn btn-primary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
    @endif
    <a class="btn btn-secondary" href="javascript:history.back()">{{ __('v216::errors.common.previous') }}</a>
@endsection
