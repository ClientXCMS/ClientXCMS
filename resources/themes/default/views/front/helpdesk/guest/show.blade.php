<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */
?>
@extends('layouts/front')
@section('title', __('helpdesk.guest.tracking_title', ['subject' => $ticket->subject]))
@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8">
        @include('shared/alerts')

        <div class="card mb-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ $ticket->subject }}
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('helpdesk.guest.opened_at', ['date' => $ticket->created_at->isoFormat('LLL')]) }}
                        · {{ $ticket->recipientEmail() }}
                    </p>
                </div>
                <x-badge-state state="{{ $ticket->status }}" />
            </div>

            <div class="mt-3 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 text-xs text-amber-700 dark:text-amber-300">
                {{ __('helpdesk.guest.bookmark_warning') }}
            </div>
        </div>

        <div class="space-y-3">
            @foreach ($ticket->messages as $message)
                <div class="card">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <span>
                            @if ($message->admin_id)
                                <strong>{{ __('helpdesk.guest.staff_reply') }}</strong>
                            @elseif ($message->customer_id)
                                <strong>{{ $ticket->recipientName() }}</strong>
                            @else
                                <strong>{{ $ticket->recipientName() ?: __('helpdesk.guest.you') }}</strong>
                            @endif
                        </span>
                        <span>{{ $message->created_at->isoFormat('LLL') }}</span>
                    </div>
                    <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line">
                        {{ $message->message }}
                    </div>
                </div>
            @endforeach
        </div>

        @if ($ticket->status !== \App\Models\Helpdesk\SupportTicket::STATUS_CLOSED)
            <form method="POST" action="{{ route('front.support.guest.reply', ['token' => $token]) }}" class="card mt-4">
                @csrf
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">
                    {{ __('helpdesk.guest.reply_title') }}
                </h2>
                @include('shared/textarea', [
                    'name' => 'message',
                    'label' => __('helpdesk.message'),
                    'required' => true,
                    'rows' => 6,
                ])
                <button type="submit" class="btn btn-primary mt-3">
                    {{ __('helpdesk.guest.reply_submit') }}
                </button>
            </form>
        @else
            <div class="card mt-4 text-center text-sm text-gray-500">
                {{ __('helpdesk.guest.closed_notice') }}
            </div>
        @endif
    </div>
@endsection
