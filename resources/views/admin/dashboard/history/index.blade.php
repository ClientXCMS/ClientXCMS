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

@section('title', __('admin.history.title'))
@extends('admin.layouts.admin')
@section('content')
    <div class="container mx-auto">
    @include('admin/shared/alerts')
    <div class="grid sm:grid-cols-6 grid-cols-1 gap-4">
        <div class="col-span-1">
    <div class="max-w-xs flex flex-col">
                @foreach($folders as $folder)
                    <div class="max-w-xs flex flex-col">
                            <?php
                            \App\Services\Core\LogsReaderService::DirectoryTreeStructure( $storage_path, $structure );
                            ?>

                    </div>
                @endforeach
                @foreach($files as $file)
                    <a href="?l={{ \Illuminate\Support\Facades\Crypt::encrypt($file) }}"
                       class="inline-flex items-center gap-x-2 py-3 px-4 text-sm font-medium bg-white border border-gray-200  -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-gray-800 dark:border-gray-700 @if ($current_file == $file) text-primary dark:text-primary @else text-gray-800 dark:text-white @endif">
                        <i class="bi bi-file-earmark"></i> {{$file}}
                    </a>
                @endforeach
            </div>
        </div>
    <div class="col-span-5">

    <div class="card">

        <div class="card-heading">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ __('admin.history.file', ['file' => $current_file]) }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.history.subheading') }}
                </p>
            </div>

            <div class="flex">

                @if($current_file)
                    <a class="btn btn-secondary mr-2" href="{{ route('admin.history.download')  }}?dl={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
                        <span class="bi bi-download"></span>
                    </a>

                    <a class="btn btn-primary mr-2" href="{{ route('admin.history.clear')  }}?clean={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
                        <span class="bi bi-arrow-repeat"></span>
                    </a>

                    <a class="btn btn-danger mr-2" href="{{ route('admin.history.delete')  }}?del={{ \Illuminate\Support\Facades\Crypt::encrypt($current_file) }}{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
                        <span class="bi bi-trash"></span>
                    </a>
                    @if(count($files) > 1)
                        <a class="btn btn-danger" href="{{ route('admin.history.deleteall')  }}?delall=true{{ ($current_folder) ? '&f=' . \Illuminate\Support\Facades\Crypt::encrypt($current_folder) : '' }}">
                            <span class="bi bi-trash2"></span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
                {!! nl2br($content) !!}
    </div>
        </div>
    </div>
    </div>
    @endsection

