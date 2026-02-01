{{-- Story 3.6: Bulk Action Bar --}}
{{-- "Sélectionner" toggle + sticky action bar --}}
{{-- SweetAlert2 confirmation before bulk enable/disable --}}
{{-- ARIA: role="toolbar", aria-label="Actions groupées" --}}

{{-- Bulk Action Bar (sticky, hidden by default - shown when bulk mode active) --}}
<div id="bulk-action-bar"
    class="hidden fixed bottom-0 left-0 right-0 md:sticky md:bottom-auto md:top-4 z-40"
    role="toolbar"
    aria-label="Actions groupées">
    <div class="bg-white dark:bg-slate-900 border-t md:border border-gray-200 dark:border-slate-700 md:rounded-xl shadow-2xl md:shadow-lg p-3">
        <div class="flex items-center justify-between gap-3 max-w-4xl mx-auto">
            {{-- Selection count --}}
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                    <i class="bi bi-check2-square text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <span id="bulk-count" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    0 sélectionnée
                </span>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center gap-2">
                <button type="button"
                    data-action="bulk-activate"
                    disabled
                    class="px-3 py-1.5 text-xs font-medium bg-green-500 hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors">
                    <i class="bi bi-check-circle mr-1"></i> {{ __('extensions.settings.enable') }}
                </button>

                <button type="button"
                    data-action="bulk-deactivate"
                    disabled
                    class="px-3 py-1.5 text-xs font-medium bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors">
                    <i class="bi bi-ban mr-1"></i> {{ __('extensions.settings.disable') }}
                </button>

                <button type="button"
                    data-action="bulk-cancel"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 border border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-lg transition-colors">
                    {{ __('global.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
