@extends('errors._layout')

@section('status_code', '500')
@section('title', __('v216::errors.500.title'))
@section('heading', __('v216::errors.500.heading'))
@section('description', __('v216::errors.500.description'))

@section('illustration')
    {{-- Gear with a single bolt — communicates "machinery problem" --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06A2 2 0 1 1 4.29 16.96l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
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
    @if(\Illuminate\Support\Facades\Route::has('front.support.create'))
        <a class="btn btn-secondary" href="{{ route('front.support.create') }}">{{ __('v216::errors.common.support') }}</a>
    @endif
@endsection
