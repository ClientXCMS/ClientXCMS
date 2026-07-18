@extends('errors._layout')
@section('title', __('errors.422.title'))
@section('content')
    @include('shared.errors.panel', ['statusCode' => 422])
@endsection
