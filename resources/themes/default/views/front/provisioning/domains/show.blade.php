<div class="card dark:text-gray-400">
    <h2 class="text-lg font-semibold dark:text-gray-300 mb-4">{{ __('provisioning.domain_manager.details') }}</h2>
    <div class="grid md:grid-cols-2 gap-4">
        <div><span class="font-semibold">{{ __('provisioning.domain_manager.domain') }}</span><br>{{ $domain?->domain ?? $service->name }}</div>
        <div><span class="font-semibold">{{ __('global.status') }}</span><br>{{ $domain?->status ?? $service->status }}</div>
        <div><span class="font-semibold">{{ __('provisioning.domain_manager.creation_date') }}</span><br>{{ $domain?->createdAt?->format('d/m/Y') ?? '-' }}</div>
        <div><span class="font-semibold">{{ __('provisioning.domain_manager.expiration_date') }}</span><br>{{ $domain?->expiresAt?->format('d/m/Y') ?? '-' }}</div>
    </div>
</div>
