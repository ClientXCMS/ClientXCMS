{{-- Story 3.1: Cart Drawer - Preline UI offcanvas style --}}
{{-- 320-380px desktop, fullscreen overlay mobile --}}
{{-- ARIA: role="dialog", aria-label, focus trap, Escape to close --}}

{{-- Overlay --}}
<div id="cart-overlay"
    class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 z-[59] opacity-0 transition-opacity duration-300"
    aria-hidden="true"></div>

{{-- Drawer Panel --}}
<div id="cart-drawer"
    class="fixed top-0 right-0 h-full w-full sm:w-[360px] bg-white dark:bg-slate-900 shadow-2xl z-[60] transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col"
    role="dialog"
    aria-label="{{ __('extensions.settings.cart.title') }}"
    tabindex="-1">

    {{-- Header --}}
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-cart3 text-indigo-500"></i>
            <span>{{ __('extensions.settings.cart.title') }}</span>
        </h2>
        <button type="button"
            data-action="close-cart"
            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="{{ __('extensions.settings.cart.close') }}">
            <i class="bi bi-x-lg text-xl"></i>
        </button>
    </div>

    {{-- Cart Content (scrollable) --}}
    <div class="flex-1 overflow-y-auto p-4">
        {{-- Empty State --}}
        <div id="cart-empty" class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-cart text-3xl text-gray-300 dark:text-slate-600"></i>
            </div>
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('extensions.settings.cart.empty') }}</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">{{ __('extensions.settings.cart.empty_hint') }}</p>
        </div>

        {{-- Cart Items (populated by JS) --}}
        <div id="cart-items" class="space-y-2"></div>
    </div>

    {{-- Footer with Actions --}}
    <div id="cart-actions" class="hidden border-t border-gray-200 dark:border-slate-700 p-4 space-y-3">
        {{-- Cart total --}}
        <div id="cart-total" class="text-sm font-medium text-gray-500 dark:text-gray-400 text-center"></div>

        {{-- Install buttons (shown for installable extensions) --}}
        <div id="cart-install-btns" class="space-y-2">
            <button type="button"
                data-action="batch-install-activate"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-colors shadow-sm">
                <i class="bi bi-download"></i>
                {{ __('extensions.settings.cart.install_activate') }}
            </button>

            <button type="button"
                data-action="batch-install-only"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-xl font-medium transition-colors">
                <i class="bi bi-cloud-download"></i>
                {{ __('extensions.settings.cart.install_only') }}
            </button>
        </div>

        {{-- Update button (shown when cart contains only updates) --}}
        <button type="button"
            id="cart-update-btn"
            data-action="batch-update"
            class="hidden w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium transition-colors shadow-sm">
            <i class="bi bi-arrow-up-circle"></i>
            {{ __('extensions.settings.cart.update_selection') }}
        </button>
    </div>
</div>
