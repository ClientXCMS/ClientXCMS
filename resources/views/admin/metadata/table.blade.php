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

@php(
    $items = $items ?? [$item]
)

@foreach ($items as $i => $item)
    @if (count($items) > 1)
        <hr class="my-4">
        <h3 class="font-bold text-gray-800 dark:text-white ml-3">{{ get_class($item) }}  # {{ $item->id }}</h3>
    @endif
    <form action="{{ route('admin.metadata.update') }}" method="POST" class="p-4">
        @csrf
        <input type="hidden" name="model" value="{{ get_class($item) }}">
        <input type="hidden" name="model_id" value="{{ $item->id }}">

        @method('PATCH')
        <div id="metadata-container-{{ $i }}" data-count="0">
            @foreach($item->metadata as $metadata)
                @if (strlen($metadata->value) < 25)
                    <div class="grid grid-cols-2 gap-1">
                <span class="block py-2 flex flex-col">
                    <input type="text" name="metadata_key[{{ $metadata->id }}]" value="{{ $metadata->key }}" class="input-text" placeholder="{{ __('global.key') }}">
                </span>
                        <span class="block py-2 flex flex-col">
                    <div class="flex rounded-lg shadow-sm">
                        <input type="text" name="metadata_value[{{ $metadata->id }}]" value="{{ $metadata->value }}" class="py-3 px-4 block w-full input-text" placeholder="{{ __('global.value') }}">
                            <button type="button" onclick="deleteRow{{ $i }}(this)" class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent text-white disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                <span class="h-full py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </span>
                            </button>
                        </div>
                    </span>
                    </div>
                @else

                    <div class="grid grid-cols-12 gap-1">
                <span class="block py-2 flex flex-col col-span-11">
                    <input type="text" name="metadata_key[{{ $metadata->id }}]" value="{{ $metadata->key }}" class="input-text" placeholder="{{ __('global.key') }}">
                </span>
                        @if (staff_has_permission('admin.manage_metadata'))
                        <span class="block py-2 flex flex-col">
                    <div class="flex rounded-lg shadow-sm">
                            <button type="button" onclick="deleteRow{{ $i }}(this)" class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent text-white disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                <span class="h-full py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </span>
                            </button>
                        </div>
                    </span>
                        @endif
                        <div class="block py-2 flex flex-col col-span-12">
                            <textarea name="metadata_value[{{ $metadata->id }}]" rows="5" placeholder="{{ __('global.value') }}" class="py-3 px-4 block w-full input-text">{{ json_decode($metadata->value) != null ? json_encode(json_decode($metadata->value), JSON_PRETTY_PRINT) : $metadata->value }}</textarea>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="mb-4 mt-4">
            @csrf
            @method('PUT')
            @if (staff_has_permission('admin.manage_metadata'))
            <button type="button" class="btn btn-secondary" onclick="addRow{{ $i }}()">{{ __('admin.metadata.add') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('global.save') }}</button>
            @if ($loop->last)
            <p class="text-warning">{{ __('admin.metadata.editwarning') }}</p>
                @endif
                @endif
        </div>
    </form>

    <div id="fake-container-{{ $i }}" class="hidden">

        <div class="grid grid-cols-2 gap-1">
                <span class="block py-2 flex flex-col">
                    <input type="text" name="metadata_key[0]" class="input-text" placeholder="{{ __('global.key') }}">
                </span>
            <span class="block py-2 flex flex-col">
                    <div class="flex rounded-lg shadow-sm">
                        <input type="text" name="metadata_value[0]" class="py-3 px-4 block w-full input-text" placeholder="{{ __('global.value') }}">
                            <button type="button" onclick="deleteRow{{ $i }}(this)" class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-e-md border border-transparent text-white disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                                <span class="h-full py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </span>
                            </button>
                        </div>
                    </span>
        </div>
    </div>

    <script>

        function addRow{{ $i }}() {
            const table = document.querySelector('#metadata-container-{{ $i }}');
            const count = table.getAttribute('data-count');
            const content = document.querySelector('#fake-container-{{ $i }}').innerHTML;
            table.setAttribute('data-count', parseInt(count) - 1);
            table.insertAdjacentHTML("afterbegin", content.replaceAll('[0]', '[' + count + ']'));
        }

        function deleteRow{{ $i }}(button) {
            const row = button.parentNode.parentNode.parentNode;
            const table = document.querySelector('#metadata-container-{{ $i }}');
            const count = table.getAttribute('data-count');
            table.setAttribute('data-count', parseInt(count) + 1);
            row.parentNode.removeChild(row);
        }

    </script>
@endforeach
