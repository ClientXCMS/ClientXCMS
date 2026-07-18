@extends('errors._layout')
@section('title', __('errors.429.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 429])
@endsection
