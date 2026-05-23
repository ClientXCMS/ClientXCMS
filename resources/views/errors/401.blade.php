@extends('errors._layout')

@section('status_code', '401')
@section('title', __('v216::errors.401.title'))
@section('heading', __('v216::errors.401.heading'))
@section('description', __('v216::errors.401.description'))

@section('illustration')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="4" y="10" width="16" height="11" rx="2"/>
        <path d="M8 10V7a4 4 0 1 1 8 0v3"/>
    </svg>
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ route('login') }}">{{ __('global.login', ['default' => 'Sign in']) }}</a>
    <a class="btn btn-secondary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
@endsection
