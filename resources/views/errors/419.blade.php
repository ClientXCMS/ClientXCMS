@extends('errors._layout')
@section('title', __('errors.419.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 419])
@endsection
