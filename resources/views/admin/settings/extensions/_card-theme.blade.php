@php
$tags = collect($extension->api['tags'] ?? []);
$tagSlugs = $tags->pluck('slug')->implode(',');
@endphp
<div class="extension-item group bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-2xl hover:border-violet-300 dark:hover:border-violet-500 transition-all duration-300"
    data-category="themes"
    data-tags="{{ $tagSlugs }}"
    data-name="{{ $extension->name() }}"
    data-description="{{ $extension->api['short_description'] ?? '' }}">

    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-800 dark:to-slate-900 overflow-hidden">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="{{ $extension->name() }}">
        @else
        <div class="w-full h-full flex items-center justify-center">
            <i class="bi bi-palette text-5xl text-gray-300 dark:text-slate-600"></i>
        </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-6">
            @if (isset($extension->api['route']))
            <a href="{{ $extension->api['route'] }}" target="_blank" class="px-4 py-2 bg-white/90 text-gray-900 rounded-lg font-medium text-sm hover:bg-white transition-colors">
                <i class="bi bi-eye mr-2"></i>{{ __('global.details') }}
            </a>
            @endif
        </div>

        <div class="absolute top-3 right-3">
            @if($extension->isEnabled())
            <span class="inline-flex items-center py-1.5 px-3 rounded-lg text-xs font-medium bg-green-500 text-white shadow-lg">
                <i class="bi bi-check-circle-fill mr-1"></i>{{ __('extensions.settings.active') }}
            </span>
            @elseif($extension->isInstalled())
            <span class="inline-flex items-center py-1.5 px-3 rounded-lg text-xs font-medium bg-blue-500 text-white shadow-lg">
                <i class="bi bi-check mr-1"></i>{{ __('extensions.settings.installed') }}
            </span>
            @endif
        </div>

        @if ($extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<'))
            <div class="absolute top-3 left-3">
            <span class="inline-flex items-center py-1.5 px-2.5 rounded-lg text-xs font-medium bg-amber-500 text-white shadow-lg animate-pulse">
                <i class="bi bi-arrow-up-circle-fill mr-1"></i>{{ $extension->getLatestVersion() }}
            </span>
    </div>
    @endif
</div>

<div class="p-5">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                {{ $extension->name() }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $extension->author() }}</p>
        </div>
        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $extension->price(true) }}</span>
    </div>

    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
        {{ Str::limit($extension->api['short_description'] ?? '', 100) }}
    </p>

    <div class="flex flex-col gap-2">
        @if ($extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<'))
            <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST" class="ajax-extension-form">
            @csrf
            <button class="w-full btn btn-warning btn-sm flex items-center justify-center gap-1">
                <i class="bi bi-download"></i>{{ __('extensions.settings.update') }}
            </button>
            </form>
            @endif

            @if ($extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="w-full btn btn-danger btn-sm flex items-center justify-center gap-1">
                    <i class="bi bi-ban"></i>{{ __('extensions.settings.disabled') }}
                </button>
            </form>
            @elseif ($extension->isInstalled() && !$extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="w-full btn btn-success btn-sm flex items-center justify-center gap-1">
                    <i class="bi bi-check-circle"></i>{{ __('extensions.settings.enable') }}
                </button>
            </form>
            @elseif ($extension->isNotInstalled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST" class="ajax-extension-form">
                @csrf
                <button class="w-full btn btn-primary btn-sm flex items-center justify-center gap-1">
                    <i class="bi bi-cloud-download"></i>{{ __('extensions.settings.install') }}
                </button>
            </form>
            @else
            <a class="w-full btn btn-primary btn-sm flex items-center justify-center gap-1" href="{{ $extension->api['route'] }}" target="_blank">
                <i class="bi bi-cart"></i>{{ __('extensions.settings.buy') }}
            </a>
            @endif

            @if (isset($extension->api['route']))
            <a class="w-full btn btn-secondary btn-sm flex items-center justify-center gap-1" href="{{ $extension->api['route'] }}" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>{{ __('global.details') }}
            </a>
            @endif
    </div>
</div>
</div>