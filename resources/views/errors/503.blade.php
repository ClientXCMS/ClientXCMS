@extends('errors._layout')

@section('status_code', '503')
@section('title', __('v216::errors.503.title'))
@section('heading', __('v216::errors.503.heading'))
@section('description', __('v216::errors.503.description'))

@section('illustration')
    {{-- Tools (wrench + screwdriver) - maintenance vibe --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a2 2 0 1 0 2.8 2.8l6-6a4 4 0 0 0 5.4-5.4L15 9 12 6Z"/>
    </svg>
@endsection
{{-- No action by design — service is down. Auto-refresh every 30s. --}}
@push('head')
    <meta http-equiv="refresh" content="30">
@endpush
