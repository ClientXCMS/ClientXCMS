<?php
/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 *
 * Renders the floating bulk-actions bar inside a [data-bulk-root].
 * Caller must pass:
 *   $actions = ['delete' => __('global.delete'), 'suspend' => __('...')]
 *
 * @vite(['resources/global/js/admin/bulk-actions.js', 'resources/global/css/bulk-actions.css'])
 * is loaded inside this partial so a page that only needs bulk actions
 * doesn't have to register the assets manually.
 */
?>
@vite(['resources/global/js/admin/bulk-actions.js', 'resources/global/css/bulk-actions.css'])

<div data-bulk-bar class="dark:bg-gray-900">
    <span>{{ $selectedLabel ?? __('admin.selected', ['default' => 'Sélection']) }} :
        <span data-bulk-count>0</span>
    </span>
    @foreach ($actions ?? [] as $key => $label)
        <button type="button"
                data-bulk-action="{{ $key }}"
                data-bulk-confirm="{{ $confirmLabel ?? __('admin.doyouwantreally') }}">
            {{ $label }}
        </button>
    @endforeach
</div>
