<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
?>
?>
?>
@extends('layouts/client')

@section('scripts')
    <script src="{{ Vite::asset('resources/themes/default/js/popupwindow.js') }}" type="module" defer></script>
@endsection

@section('title', __('client.emails.index'))

@section('content')
    @include("shared.alerts")

    <div class="container py-5">
        <div class="card">
            <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h5 mb-1">{{ __('client.emails.index') }}</h2>
                    <p class="text-muted small mb-0">{{ __('client.emails.index_description') }}</p>
                </div>
                <div>
                    <form class="input-group">
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('global.search') }}" aria-label="{{ __('global.search') }}">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('client.emails.subject') }}</th>
                            <th class="text-end">{{ __('global.date') }}</th>
                            <th class="text-end">{{ __('global.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if (count($emails) == 0)
                            <tr>
                                <td colspan="3" class="text-center py-3">
                                    <p class="text-muted">{{ __('global.no_results') }}</p>
                                </td>
                            </tr>
                        @endif
                        @foreach($emails as $email)
                            <tr>
                                <td>
                                    <a href="{{ route('front.emails.show', $email->id) }}" is="popup-window" class="text-primary text-decoration-none">
                                        {{ $email->subject }}
                                    </a>
                                </td>
                                <td class="text-end text-muted">
                                    {{ $email->created_at->format('d/m/y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('front.emails.show', ['email' => $email->id]) }}" class="btn btn-outline-primary btn-sm" is="popup-window">
                                        <i class="bi bi-eye-fill"></i> {{ __('global.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($emails->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $emails->links('shared.layouts.pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
