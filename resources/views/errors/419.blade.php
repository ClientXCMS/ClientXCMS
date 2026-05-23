@extends('errors._layout')

@section('status_code', '419')
@section('title', __('v216::errors.419.title'))
@section('heading', __('v216::errors.419.heading'))
@section('description', __('v216::errors.419.description'))

@section('illustration')
    {{-- Clock with a small bolt to signal "session expired" --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 2"/>
    </svg>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ url()->previous() }}">{{ __('v216::errors.419.reload') }}</a>
    <a class="btn btn-secondary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
@endsection
