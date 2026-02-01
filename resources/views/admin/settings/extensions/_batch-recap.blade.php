{{-- Story 3.4: Batch Recap --}}
{{-- Centered modal with backdrop (z-[70]: above cart drawer, below extension modal) --}}
{{-- Full success: green, Partial: amber, Full failure: red --}}
{{-- "Fermer" button: POST /extensions/clear-cache then window.location.reload() (PA-3) --}}
{{-- ARIA: role="alert", aria-live="polite" --}}

<div id="batch-recap"
    class="hidden fixed inset-0 z-[70] overflow-y-auto"
    role="alert"
    aria-live="polite"
    aria-hidden="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity"></div>

    {{-- Centering container --}}
    <div class="min-h-screen px-4 flex items-center justify-center">
        {{-- Panel --}}
        <div class="relative bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

            {{-- Recap Header (dynamically colored by JS: green/amber/red) --}}
            <div id="recap-header" class="p-4 border-b border-gray-200 dark:border-slate-700">
                {{-- Content set by JS --}}
            </div>

            {{-- Per-Extension Details --}}
            <div id="recap-details" class="p-4 max-h-60 overflow-y-auto space-y-2">
                {{-- Populated dynamically by JS --}}
            </div>

            {{-- Footer with Close Button --}}
            <div class="p-4 border-t border-gray-200 dark:border-slate-700">
                <button type="button"
                    id="recap-close-btn"
                    data-action="close-recap"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 rounded-xl font-medium transition-colors">
                    <i class="bi bi-check-lg"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
