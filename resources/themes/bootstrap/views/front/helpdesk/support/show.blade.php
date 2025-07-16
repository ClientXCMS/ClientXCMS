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
@section('title', $ticket->subject)
@section('styles')
    <link rel="stylesheet" href="{{ Vite::asset('resources/global/css/simplemde.min.css') }}">
@endsection
@section('scripts')
    <script src="{{ Vite::asset('resources/global/js/mdeditor.js') }}" type="module"></script>
@endsection
@section('content')
    <div class="container py-5">
        @include('shared/alerts')
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">
                            <x-badge-state state="{{ $ticket->status }}"></x-badge-state>
                            {{ $ticket->subject }}
                        </h2>
                        <p class="text-muted small">{{ __('helpdesk.support.show.index_description') }}</p>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            @foreach ($ticket->messages as $i => $message)
                                <li class="mb-4 {{ $message->containerClasses() }}">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title d-flex align-items-center">
                                                <div class="me-3">
                                            <span class="rounded-circle bg-secondary text-white d-inline-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                                {{ $message->initials() }}
                                            </span>
                                                </div>
                                                {{ $message->replyText($i) }}
                                            </h6>
                                            <p class="card-text">{!! $message->formattedMessage() !!}</p>
                                            <p class="text-muted small">
                                                {{ $message->created_at->format('d/m/y H:i') }}
                                                @if ($message->isStaff()) - {{ $message->staffUsername() }} @endif
                                            </p>
                                            @if ($message->hasAttachments($ticket->attachments))
                                                <div>
                                                    @foreach ($message->getAttachments($ticket->attachments) as $attachment)
                                                        <a href="{{ route('front.support.download', ['ticket' => $ticket, 'attachment' => $attachment]) }}" class="text-primary text-decoration-none">
                                                            <i class="bi bi-file-earmark"></i> {{ Str::limit($attachment->filename, 30) }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if ($ticket->isOpen())
                            <h5 class="mt-4">{{ __('helpdesk.support.show.replyinticket') }}</h5>
                            <form method="POST" action="{{ route('front.support.reply', ['ticket' => $ticket]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <textarea id="editor" name="content" class="form-control">{{ old('content') }}</textarea>
                                    @if ($errors->has('content'))
                                        @foreach ($errors->get('content') as $error)
                                            <div class="text-danger small mt-2">{{ $error }}</div>
                                        @endforeach
                                    @endif
                                </div>
                                @if (setting('helpdesk_allow_attachments'))
                                    <div class="mb-3">
                                        @include('shared/file2', ['name' => 'attachments', 'label' => __('helpdesk.support.attachments'), 'help' => __('helpdesk.support.attachments_help', ['size' => setting('helpdesk_attachments_max_size'), 'types' => formatted_extension_list(setting('helpdesk_attachments_allowed_types'))])])
                                    </div>
                                @endif
                                <button type="submit" class="btn btn-primary">{{ __('helpdesk.support.show.reply') }}</button>
                                <button type="submit" name="close" class="btn btn-secondary">{{ __('helpdesk.support.show.replyandclose') }}</button>
                            </form>
                        @else
                            <div class="alert alert-warning mt-3">
                                {{ $ticket->close_reason ? __('helpdesk.support.show.closed2', ['reason' => $ticket->close_reason]) : __('helpdesk.support.show.closed3') }}

                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <!-- Users -->
                        <div class="mb-3">
                            @foreach ($ticket->attachedUsers() as $initials => $username)
                                <span class="badge bg-secondary text-white me-2" title="{{ $username }}">
                            {{ $initials }}
                        </span>
                            @endforeach
                        </div>

                        <!-- Details -->
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="bi bi-buildings me-2"></i> {{ $ticket->department->name }}
                            </li>
                            @if ($ticket->isValidRelated())
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-box me-2"></i> {{ $ticket->related->relatedName() }}
                                </li>
                            @endif
                            <li class="list-group-item d-flex align-items-center">
                                <i class="bi bi-send-dash me-2"></i> {{ __('helpdesk.priority') }}  <x-badge-state state="{{ $ticket->priority }}"></x-badge-state>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="bi bi-calendar-date me-2"></i> {{ __('helpdesk.support.show.open_on', ['date' => $ticket->created_at->format('d/m H:i')]) }}
                            </li>
                            @if ($ticket->closed_at)
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-x-square me-2"></i> {{ __('helpdesk.support.show.closed_on', ['date' => $ticket->closed_at->format('d/m H:i')]) }}
                                </li>
                            @endif
                        </ul>

                        <!-- Attachments -->
                        @if ($ticket->attachments->count() > 0)
                            <ul class="list-group mt-3">
                                @foreach ($ticket->attachments as $attachment)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ Str::limit($attachment->filename, 30) }}</span>
                                        <a href="{{ route('front.support.download', ['ticket' => $ticket, 'attachment' => $attachment]) }}" class="text-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <!-- Close/Reopen Ticket -->
                        @if ($ticket->isOpen())
                            <form method="POST" action="{{ route('front.support.close', ['ticket' => $ticket]) }}" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary w-100">{{ __('helpdesk.support.show.close') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('front.support.reopen', ['ticket' => $ticket]) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">{{ __('helpdesk.support.show.reopen') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
