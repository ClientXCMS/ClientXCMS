@extends('admin/settings/sidebar')
@section('title', __($translatePrefix . '.show.title', ['name' => $item->extension]))
@section('setting')
    <div class="container mx-auto">
        <form method="POST" class="card" action="{{ route($routePath . '.update', ['domain_tld' => $item]) }}">
            @csrf
            @method('PUT')
            @include('admin.provisioning.domain-tlds.form')
        </form>
    </div>
@endsection
