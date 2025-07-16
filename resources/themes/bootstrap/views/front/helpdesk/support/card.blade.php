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
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-1">{{ __('helpdesk.support.index') }}</h2>
                <p class="text-muted small mb-0">{{ __('helpdesk.support.index_description') }}</p>
            </div>
            <div class="d-flex gap-2">
                @if(isset($count) && $count > 3)
                    <a href="{{ route('front.services.index') }}" class="btn btn-link text-primary p-0">
                        {{ __('global.seemore') }}
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
                @if (!isset($count))
                    <a href="{{ route('front.support.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> {{ __('helpdesk.support.create.newticket') }}
                    </a>
                @endif
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> {{ __('global.filter') }}
                        @if ($filter)
                            <span class="badge bg-primary ms-2">{{ count($tickets) }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                        @foreach ($filters as $current)
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" id="filter-service-{{ $current }}" value="{{ $current }}" class="form-check-input me-2" data-redirect="{{ route('front.support.index') }}" @if ($current == $filter) checked @endif>
                                    {{ __('global.states.' . $current) }}
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>{{ __('helpdesk.subject') }}</th>
                    <th>{{ __('helpdesk.priority') }}</th>
                    <th>{{ __('global.status') }}</th>
                    <th>{{ __('global.created') }}</th>
                    <th class="text-end">{{ __('global.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @if (count($tickets) == 0)
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            {{ __('global.no_results') }}
                        </td>
                    </tr>
                @endif
                @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->excerptSubject() }}</td>
                        <td> <x-badge-state state="{{ $ticket->priority }}"></x-badge-state></td>
                        <td>
                            <x-badge-state state="{{ $ticket->status }}"></x-badge-state>
                        </td>
                        <td>{{ $ticket->created_at->format('d/m/y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('front.support.show', ['ticket' => $ticket]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye-fill"></i> {{ __('global.show') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if ($tickets->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $tickets->links('shared.layouts.pagination') }}
            </div>
        </div>
    @endif
</div>
