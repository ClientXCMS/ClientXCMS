@extends('errors._layout')
@section('title', __('errors.403.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 403])
@endsection
