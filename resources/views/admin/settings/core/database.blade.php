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
@extends('admin.settings.sidebar')
@section('title', __('admin.database.title'))
@section('setting')
    <div class="card">
        <div class="flex justify-between">
            <div>
                <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {{ __('admin.database.title') }}
                </h4>
                <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
                    {{ __('admin.database.description') }}
                </p>
            </div>
            @if (app('license')->getLicense()->getServer() != null)
            <div>
                <a href="https://ctx-{{ app('license')->getLicense()->getServer() }}-pma.clientxcms.com" target="_blank" class="btn-primary">{{ __('admin.database.pmaaccess') }} <i class="bi bi-box-arrow-up-right"></i></a>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
            <div>
                <form method="POST" class="mt-4" action="{{ route('admin.database.index') }}">
                    @csrf
                    <p class="text-gray-800 dark:text-gray-400">{{ __('admin.database.migrateextension')  }}</p>
                    @include('admin/shared/select', ['label' => null, 'options' => $extensions, 'name' => 'extension', 'value' => null])
                    <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-database-add mr-3"></i> {{ __('admin.database.migrate') }}</button>
                    <button type="submit" class="btn btn-secondary mt-2" name="seed"><i class="bi bi-database-fill-gear mr-3"></i> {{ __('admin.database.seed') }}</button>

                </form>
            </div>
                <div>

                <ul class="space-y-3 text-sm">
                    <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                        <span class="text-gray-800 dark:text-gray-400">
                            @if (app('license')->getLicense()->getServer() != null)
      {{ __('admin.database.host') }} : ctx-{{ app('license')->getLicense()->getServer() }}.clientxcms.com
                            @else
        {{ __('admin.database.host') }} : {{ $_ENV['DB_HOST'] }}
                            @endif
                        </span>
                    </li>
                    <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                        <span class="text-gray-800 dark:text-gray-400">
      {{ __('admin.database.port') }} : {{ $_ENV['DB_PORT'] }}
    </span>
                    </li>

                    <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                        <span class="text-gray-800 dark:text-gray-400">
      {{ __('admin.database.username') }} : {{ $_ENV['DB_USERNAME'] }}
    </span>
                    </li>

                    <li class="flex space-x-3">
    <span class="h-5 w-5 flex justify-center items-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-800/30 dark:text-blue-500">
      <svg class="flex-shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
                        <span class="text-gray-800 dark:text-gray-400">
                                {{ __('admin.database.password') }} :<br/> <span class="blur">{{ $_ENV['DB_PASSWORD'] }}</span>
    </span>
                    </li>
                </ul>
                </div>
            </div>
        </div>
        @if($output = Session::get('output'))

            <div class="mt-4 card">
                <p class="text-secondary mb-3">{{ __('admin.database.output') }}</p>
                <pre class="text-xs text-gray-600 dark:text-gray-400">{{ $output }}</pre>
            </div>
    @endif
@endsection
