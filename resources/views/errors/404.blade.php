@extends('errors._layout')
@section('title', __('errors.404.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 404])
@endsection
