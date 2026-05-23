@extends('errors._layout')

@section('status_code', '429')
@section('title', __('v216::errors.429.title'))
@section('heading', __('v216::errors.429.heading'))
@section('description', __('v216::errors.429.description'))

@section('illustration')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 2v4"/>
        <path d="m4.93 4.93 2.83 2.83"/>
        <path d="M2 12h4"/>
        <path d="m4.93 19.07 2.83-2.83"/>
        <path d="M12 22v-4"/>
        <path d="m19.07 19.07-2.83-2.83"/>
        <path d="M22 12h-4"/>
        <path d="m19.07 4.93-2.83 2.83"/>
    </svg>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ url()->previous() }}">{{ __('v216::errors.common.previous') }}</a>
@endsection
