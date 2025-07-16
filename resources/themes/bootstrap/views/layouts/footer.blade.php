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

<footer class="mt-auto py-10 sm:px-6 lg:px-8 mx-auto dark:bg-gray-900 border-gray-700 shadow-sm">
    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-5 max-w-7xl mx-2 lg:mx-auto">
        <div>

            <a class="flex-none text-xl font-semibold text-black dark:text-white" href="#" aria-label="{{ setting('app_name') }}">
                <img src="{{ setting('app_logo_text') }}" class="h-12 w-auto mt-4">
            </a>
            <p class="mx-auto ml-auto mt-4 text-gray-500 dark:text-neutral-500">
                {!! translated_setting('theme_footer_description') !!}
            </p>
        </div>
        <div class="col-span-2 flex justify-start sm:justify-end">
            {!! setting('theme_footer_topheberg') !!}
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-5 mt-2 max-w-7xl mx-auto">
        <div>

        </div>
        <!-- End Col -->
        <div>

            <ul class="text-center">
                @foreach (app('theme')->getBottomLinks() as $link)

                    <li class="inline-block relative pe-8 last:pe-0 last-of-type:before:hidden before:absolute before:top-1/2 before:end-3 before:-translate-y-1/2 before:content-['/'] before:text-gray-300 dark:before:text-neutral-600">
                        <a class="inline-flex gap-x-2 text-sm text-gray-500 hover:text-gray-800 dark:text-neutral-500 dark:hover:text-neutral-200" href="{{ $link->trans('url') }}" {{ $link->link_type == 'new_tab' ? ' target="_blank" ' : '' }}>
                            {{ $link->trans('name') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <!-- End Col -->

        <div class="text-center md:text-end space-x-2">
            @foreach (app('theme')->getSocialsNetworks() as $network)
                <a class="size-8 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-500 hover:text-indigo-500 disabled:opacity-50 disabled:pointer-events-none dark:hover:text-indigo-700" href="{{ $network->url }}">
                    <i class="{{ $network->icon }}"></i>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-5 text-center text-gray-500 dark:text-neutral-500">
        <p class="mt-2">&copy; {{ date('Y') }} {{ setting('app_name') }}. All rights reserved.</p>
    </div>
    <!-- End Grid -->
</footer>
