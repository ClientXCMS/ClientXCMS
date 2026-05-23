@extends('errors._layout')

@section('status_code', '422')
@section('title', __('v216::errors.422.title'))
@section('heading', __('v216::errors.422.heading'))
@section('description', __('v216::errors.422.description'))

@section('illustration')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <circle cx="12" cy="17" r=".5"/>
    </svg>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ url()->previous() }}">{{ __('v216::errors.common.previous') }}</a>
    <a class="btn btn-secondary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
@endsection
