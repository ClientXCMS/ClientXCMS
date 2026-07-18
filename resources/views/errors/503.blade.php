@extends('errors._layout')
@section('title', __('errors.503.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 503])
@endsection
