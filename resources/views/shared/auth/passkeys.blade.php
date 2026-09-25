@vite('resources/global/js/passkeys.js')
@if($confirmation ?? false)
<div class="mt-4" data-passkey-confirm data-error-message="{{ __('auth.passkeys.error') }}">
    <button type="button" data-passkey-confirm-button
        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-900">
        <i class="bi bi-key-fill text-lg"></i>
        <span data-passkey-button-label>{{ __('auth.passkeys.confirm_with_passkey') }}</span>
        <svg data-passkey-spinner hidden class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path></svg>
    </button>
    <p data-passkey-error hidden role="alert" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300"></p>
</div>
@else
<div class="mt-5" data-passkey-login data-error-message="{{ __('auth.passkeys.error') }}">
    <div class="mb-4 flex items-center gap-3" aria-hidden="true">
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
        <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">{{ __('global.or') }}</span>
        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
    </div>
    <button type="button" data-passkey-login-button
        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-900">
        <i class="bi bi-key-fill text-lg"></i>
        <span data-passkey-button-label>{{ __('auth.passkeys.login') }}</span>
        <svg data-passkey-spinner hidden class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path></svg>
    </button>
    <p data-passkey-error hidden role="alert" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300"></p>
</div>
@endif
