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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */
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
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include('shared/alerts')
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">

                    <div class="grid lg:grid-cols-12 md:grid-cols-4">
                        <div class="lg:col-span-8 col-span-4">
                    <div class="card">
                        <div class="card-heading">
                            <div>

                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    <x-badge-state state="{{ $ticket->status }}"></x-badge-state>

                                    {{ $ticket->subject }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('helpdesk.support.show.index_description') }}
                                </p>
                            </div>
                        </div>

                        <div>
                            @foreach ($ticket->messages->groupBy(fn($msg) => $msg->created_at->translatedFormat('d M, Y')) as $date => $messages)
                                <!-- Heading -->
                                <div class="ps-2 my-2 first:mt-0">
                                    <h3 class="text-xs font-medium uppercase text-gray-500 dark:text-neutral-400">
                                        {{ $date }}
                                    </h3>
                                </div>
                                <!-- End Heading -->

                                @foreach ($messages as $i => $message)
                                    <!-- Item -->
                                    <div class="flex gap-x-3 relative group rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">


                                        <!-- Icon -->
                                        <div class="relative last:after:hidden after:absolute after:top-10 after:bottom-0 after:start-5 after:w-px after:-translate-x-[0.5px] after:bg-gray-200 dark:after:bg-neutral-700">
                                            <div class="relative z-10 size-10 flex justify-center items-center">
        <span class="flex shrink-0 justify-center items-center size-10 bg-white border border-gray-200 text-[10px] font-semibold uppercase text-gray-600 rounded-full dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
          <i class="bi bi-{{ $message->isStaff() ? 'person-badge' : 'person' }} text-lg"></i>
        </span>
                                            </div>
                                        </div>
                                        <!-- End Icon -->

                                        <!-- Right Content -->
                                        <div class="grow p-2 pb-2">
                                            <div class="flex justify-between">

                                                <h3 class="flex gap-x-1.5 font-semibold text-gray-800 dark:text-white">
                                                    {!! $message->replyText($i, 'customer') !!}
                                                </h3>

                                                @if($message->isCustomer() && $message->canEdit())
                                                    <button class="btn btn-sm hs-collapse-toggle ml-2" data-bs-toggle="collapse" aria-expanded="false" data-hs-collapse="#edit-message-{{ $message->id }}" title="{{ __('helpdesk.support.show.staff') }}">
                                                        <i class="bi bi-pen"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="break-words max-w-2xl">
                                                <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
                                                    {!! $message->formattedMessage() !!}
                                                </p>
                                            </div>

                                            @if ($message->hasAttachments($ticket->attachments))
                                                <div class="mt-2 space-y-1">
                                                    @foreach ($message->getAttachments($ticket->attachments) as $attachment)
                                                        <a href="{{ route('front.support.download', ['ticket' => $ticket, 'attachment' => $attachment]) }}" class="block text-sm text-blue-600 hover:underline dark:text-blue-500 dark:hover:text-blue-400">
                                                            <i class="bi bi-file-earmark"></i>
                                                            {{ Str::limit($attachment->filename, 30) }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="mt-2 flex items-center gap-x-2 text-xs text-gray-500 dark:text-neutral-400">
                                                @if ($message->isStaff())
                                                    <span class="inline-flex items-center gap-x-1">
                                <i class="bi bi-person-circle"></i> {{ $message->staffUsername() }}
                            </span>
                                                @elseif($message->isCustomer())
                                                    <span class="inline-flex items-center gap-x-1">
                                <i class="bi bi-person"></i> {{ $message->customer->excerptFullName() }}
                            </span>
                                                @endif

                                                    <span class="text-xs">· {{ $message->edited_at ? __('helpdesk.support.show.edited_at', ['date' => $message->edited_at->format('H:i')]) :$message->created_at->format('H:i') }}</span>


                                            </div>
                                        </div>
                                        <!-- End Right Content -->
                                    </div>
                                    <!-- End Item -->

                                    @if($message->isCustomer() && $message->canEdit())

                                        <div class="hs-collapse hidden" id="edit-message-{{ $message->id }}" aria-labelledby="edit-message-{{ $message->id }}-heading">
                                            <div class="card-body">
                                                <form method="POST" action="{{ route('front.support.messages.update', ['ticket' => $ticket, 'message' => $message]) }}">
                                                    @csrf
                                                    <textarea class="editor" name="content">{{ $message->message }}</textarea>
                                                    <button class="btn btn-primary mt-2">{{ __('global.save') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('front.support.messages.destroy', ['ticket' => $ticket, 'message' => $message]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger mt-2">{{ __('global.delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>

                    @if ($ticket->isOpen())
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mt-6">
                            {{ __('helpdesk.support.show.replyinticket') }}
                        </h3>
                        <form method="POST" action="{{ route('front.support.reply', ['ticket' => $ticket]) }}" enctype="multipart/form-data">
                            @csrf
                            <textarea class="editor" name="content">{{ old('content') }}</textarea>
                        @if ($errors->has('content'))
                            @foreach ($errors->get('content') as $error)
                                <div class="text-red-500 text-sm mt-2">
                                    {{ $error }}
                                </div>
                            @endforeach
                        @endif
                            @if (setting('helpdesk_allow_attachments'))
                                <div class="col-span-2 mt-2">
                                    @include('shared/file2', ['name' => 'attachments', 'label' => __('helpdesk.support.attachments'), 'help' => __('helpdesk.support.attachments_help', ['size' => setting('helpdesk_attachments_max_size'), 'types' => formatted_extension_list(setting('helpdesk_attachments_allowed_types'))])])
                                </div>
                            @endif

                            <button class="btn btn-primary mt-2">{{ __('helpdesk.support.show.reply') }}</button>
                            <button class="btn btn-secondary mt-2" name="close">{{ __('helpdesk.support.show.replyandclose') }}</button>
                        </form>
                        @else

                            <div class="alert text-yellow-800 bg-yellow-100 mt-2" role="alert">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                {{ $ticket->close_reason ? __('helpdesk.support.show.closed2', ['reason' => $ticket->close_reason]) : __('helpdesk.support.show.closed3') }}
                            </div>
                        @endif
                    </div>
                    </div>
                        <div class="lg:col-span-4 col-span-1">
                        <div class="card ml-2">
                            <div class="flex -space-x-2 mb-2">
                                @foreach ($ticket->attachedUsers() as $initials => $username)
                                <div class="hs-tooltip inline-block">
                                    <span class="hs-tooltip-toggle relative inline-flex items-center justify-center h-[2.375rem] w-[2.375rem] rounded-full bg-gray-500 font-semibold text-white leading-none">
  {{ $initials }}
</span>
                                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 inline-block absolute invisible z-20 py-1.5 px-2.5 bg-gray-900 text-xs text-white rounded-lg dark:bg-neutral-700" role="tooltip">
      {{ $username }}
    </span>
                                </div>
                                    @endforeach
                            </div>
                            <ul class="max-w-lg  flex flex-col">
                                <li class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-white">
                                    <i class="bi bi-buildings"></i>
                                    {{ $ticket->department->name }}
                                </li>
                                @if ($ticket->isValidRelated())

                                    <li class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-white">
                                        <i class="bi bi-box"></i>
                                        {{ $ticket->related->relatedName() }}
                                    </li>
                                    @endif
                                <li class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-white">
                                    <i class="bi bi-send-dash"></i>
    {{ __('helpdesk.priority') }} <x-badge-state state="{{ $ticket->priority }}"></x-badge-state>
</li>
                                <li class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-white">
                                    <i class="bi bi-calendar-date"></i>
                                    {{ __('helpdesk.support.show.open_on', ['date' => $ticket->created_at->format('d/m H:i')]) }}
                                </li>

                                    @if ($ticket->closed_at)
                                        <li class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-white">
                                            <i class="bi bi-x-square"></i>
                                            {{ __('helpdesk.support.show.closed_on', ['date' => $ticket->closed_at->format('d/m H:i')]) }}
                                        </li>
                                    @endif
                                </ul>
                            <ul class="flex flex-col justify-end text-start -space-y-px mt-3">
                                @foreach ($ticket->attachments as $attachment)
                                <li class="flex items-center gap-x-2 p-3 text-sm bg-white border text-gray-800 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                                    <div class="w-full flex justify-between truncate">
      <span class="me-3 flex-1 w-0 truncate">
         {{ Str::limit($attachment->filename, 30) }}
      </span>
                                        <a href="{{ route('front.support.download', ['ticket' => $ticket, 'attachment' => $attachment]) }}" class="flex items-center gap-x-2 text-gray-500 hover:text-blue-600 whitespace-nowrap dark:text-neutral-500 dark:hover:text-blue-500">
                                            <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                <line x1="12" x2="12" y1="15" y2="3"></line>
                                            </svg>
                                        </a>
                                    </div>
                                </li>
                                    @endforeach
                            </ul>
                                @if ($ticket->isOpen())
                            <form method="POST" action="{{ route('front.support.close', ['ticket' => $ticket]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-secondary mt-2 w-full" type="submit">{{ __('helpdesk.support.show.close') }}</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('front.support.reopen', ['ticket' => $ticket]) }}">
                                @csrf
                                <button class="btn btn-primary mt-2 w-full" type="submit">{{ __('helpdesk.support.show.reopen') }}</button>
                            </form>
                            @endif

</div>
</div>
</div>

</div>
</div>
</div>
</div>
@endsection
