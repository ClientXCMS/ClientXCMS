{{-- Story 3.5: Update Banner --}}
{{-- Amber alert when updates available, compact on mobile --}}
{{-- CTA adds all update-available extensions to cart and opens drawer --}}

@php
$updateCount = $allExtensions->filter(function($ext) {
    return $ext->isInstalled()
        && $ext->getLatestVersion()
        && version_compare($ext->version ?? '0', $ext->getLatestVersion(), '<');
})->count();
@endphp

<div id="update-banner"
    class="{{ $updateCount > 0 ? '' : 'hidden' }} mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                <i class="bi bi-arrow-up-circle-fill text-amber-600 dark:text-amber-400 text-lg"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    <span id="update-count">{{ $updateCount }} mise{{ $updateCount > 1 ? 's' : '' }} à jour disponible{{ $updateCount > 1 ? 's' : '' }}</span>
                </p>
                <p class="text-xs text-amber-600 dark:text-amber-400 hidden sm:block">
                    Mettez à jour vos extensions pour bénéficier des dernières fonctionnalités
                </p>
            </div>
        </div>
        <button type="button"
            data-action="add-updates-to-cart"
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
            <i class="bi bi-arrow-up-circle"></i>
            <span class="hidden sm:inline">Tout mettre à jour</span>
            <span class="sm:hidden">Mettre à jour</span>
        </button>
    </div>
</div>
