@extends('admin/settings/sidebar')
@section('title', __($translatePrefix . '.create.title'))
@section('setting')
    <div class="container mx-auto">
        <form method="POST" class="card" action="{{ route($routePath . '.store') }}">
            @csrf
            @include('admin.provisioning.domain-tlds.form')
        </form>
    </div>
@endsection
