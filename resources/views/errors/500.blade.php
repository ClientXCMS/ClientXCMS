@extends('errors._layout')
@section('title', __('errors.500.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 500])
@endsection
