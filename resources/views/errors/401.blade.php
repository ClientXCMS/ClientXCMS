@extends('errors._layout')
@section('title', __('errors.401.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 401])
@endsection
