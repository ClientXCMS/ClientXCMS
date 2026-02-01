{{-- Story 3.3: Batch Progress Bar --}}
{{-- Fixed bottom viewport on mobile, bottom content area on desktop --}}
{{-- ARIA: role="progressbar", aria-valuenow, aria-live --}}

<div id="batch-progress"
    class="hidden fixed bottom-0 left-0 right-0 md:relative md:bottom-auto md:left-auto md:right-auto z-50"
    aria-hidden="true">
    <div class="bg-white dark:bg-slate-900 border-t md:border border-gray-200 dark:border-slate-700 md:rounded-2xl shadow-2xl md:shadow-lg md:mt-6 md:mx-auto md:max-w-2xl overflow-hidden">

        {{-- Progress Header --}}
        <div class="p-4 border-b border-gray-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                        <i class="bi bi-arrow-repeat animate-spin text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <div id="batch-progress-text" class="text-sm font-medium text-gray-900 dark:text-white" aria-live="polite">
                            Préparation...
                        </div>
                        <div id="batch-progress-percentage" class="text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
                            0%
                        </div>
                    </div>
                </div>

                {{-- Stop Button --}}
                <button type="button"
                    data-action="batch-stop"
                    class="px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                    <i class="bi bi-stop-circle mr-1"></i> Arrêter
                </button>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                <div id="batch-progress-bar"
                    class="bg-indigo-600 dark:bg-indigo-500 h-full rounded-full transition-all duration-500 ease-out"
                    style="width: 0%"
                    role="progressbar"
                    aria-valuenow="0"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Progression de l'installation"></div>
            </div>
        </div>

        {{-- Per-Extension Progress Items --}}
        <div id="batch-progress-items" class="p-4 max-h-48 overflow-y-auto space-y-1">
            {{-- Populated dynamically by JS --}}
        </div>

        {{-- Error State (initially hidden) --}}
        <div id="batch-error" class="hidden p-4 bg-red-50 dark:bg-red-900/20 border-t border-red-200 dark:border-red-800">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-red-500"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-red-800 dark:text-red-300">
                        Erreur lors du traitement de <span id="batch-error-name" class="font-bold"></span>
                    </p>
                    <p id="batch-error-message" class="text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button type="button"
                            data-batch-action="retry"
                            class="px-3 py-1.5 text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                            <i class="bi bi-arrow-clockwise mr-1"></i> Réessayer
                        </button>
                        <button type="button"
                            data-batch-action="skip"
                            class="px-3 py-1.5 text-xs font-medium bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                            <i class="bi bi-skip-forward mr-1"></i> Passer
                        </button>
                        <button type="button"
                            data-batch-action="stop"
                            class="px-3 py-1.5 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <i class="bi bi-stop-circle mr-1"></i> Arrêter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
