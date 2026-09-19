<div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"><i class="bi bi-globe2 text-2xl" aria-hidden="true"></i></div>
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">{{ __('provisioning.domain_manager.details') }}</h2>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50 break-words"><span class="mb-2 inline-block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.domain') }}</span><br>{{ $domain?->domain ?? $service->name }}</div>
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50 break-words"><span class="mb-2 inline-block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('global.status') }}</span><br>{{ $domain?->status ?? $service->status }}</div>
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50 break-words"><span class="mb-2 inline-block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.creation_date') }}</span><br>{{ $domain?->createdAt?->format('d/m/Y') ?? '-' }}</div>
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50 break-words"><span class="mb-2 inline-block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('provisioning.domain_manager.expiration_date') }}</span><br>{{ $domain?->expiresAt?->format('d/m/Y') ?? '-' }}</div>
    </div>
</div>
