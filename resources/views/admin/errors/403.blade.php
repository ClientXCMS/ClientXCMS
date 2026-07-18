@extends('admin.layouts.admin')

@section('title', __('errors.403.title'))

@section('content')
    @include('shared.errors.panel', [
        'statusCode' => 403,
        'homeUrl' => route('admin.dashboard'),
        'homeLabel' => __('errors.common.dashboard'),
    ])
@endsection
