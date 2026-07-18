@php
    $status = (int) ($statusCode ?? 500);
    $homeUrl = $homeUrl ?? route('home');
    $homeLabel = $homeLabel ?? __('errors.common.home');
    $icon = match ($status) {
        401 => 'bi-person-lock',
        403 => 'bi-shield-lock',
        404 => 'bi-compass',
        419 => 'bi-clock-history',
        422 => 'bi-exclamation-circle',
        429 => 'bi-hourglass-split',
        503 => 'bi-tools',
        default => 'bi-exclamation-triangle',
    };
@endphp
<div class="{{ theme_metadata('layout_classes', 'mx-auto max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14') }}">
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-8 text-center dark:border-gray-700 dark:bg-gray-800/60 sm:px-10">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                    <i class="bi {{ $icon }} text-2xl" aria-hidden="true"></i>
                </div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                    {{ __('errors.common.status_code', ['code' => $status]) }}
                </p>
                <div class="mt-2 text-6xl font-bold tracking-tight text-gray-900 dark:text-white" aria-hidden="true">
                    {{ $status }}
                </div>
            </div>

            <div class="px-6 py-8 text-center sm:px-10 sm:py-10">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white sm:text-3xl">
                    {{ __('errors.'.$status.'.title') }}
                </h1>
                <p class="mt-2 font-medium text-gray-700 dark:text-gray-200">
                    {{ __('errors.'.$status.'.heading') }}
                </p>
                <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-gray-500 dark:text-gray-400 sm:text-base">
                    {{ __('errors.'.$status.'.description') }}
                </p>

                <div class="mt-7 flex flex-col-reverse justify-center gap-3 sm:flex-row">
                    <a href="{{ url()->previous() }}" class="inline-flex min-h-11 items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        {{ __('errors.common.previous') }}
                    </a>
                    <a href="{{ $homeUrl }}" class="inline-flex min-h-11 items-center justify-center gap-x-2 rounded-lg border border-transparent bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:pointer-events-none disabled:opacity-50 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        {{ $homeLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
