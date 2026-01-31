@php
$tags = collect($extension->api['tags'] ?? []);
$tagSlugs = $tags->pluck('slug')->implode(',');
@endphp
<div class="extension-item group bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl overflow-hidden hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-500 hover:-translate-y-1 transition-all duration-300"
    data-category="{{ Str::slug($groupName) }}"
    data-tags="{{ $tagSlugs }}"
    data-name="{{ $extension->name() }}"
    data-description="{{ $extension->api['short_description'] ?? '' }}">
    <div class="relative h-36 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-800 dark:to-slate-900 flex items-center justify-center {{ $extension->hasPadding() ? 'p-4' : '' }}">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300" alt="{{ $extension->name() }}">
        @else
        <i class="bi bi-puzzle text-4xl text-gray-300 dark:text-slate-600"></i>
        @endif

        <div class="absolute top-2.5 right-2.5">
            @if($extension->isEnabled())
            <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-green-500 text-white shadow-lg">
                <i class="bi bi-check-circle-fill mr-1"></i>{{ __('extensions.settings.enabled') }}
            </span>
            @elseif($extension->isInstalled())
            <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-blue-500 text-white shadow-lg">
                <i class="bi bi-check mr-1"></i>{{ __('extensions.settings.installed') }}
            </span>
            @endif
        </div>

        @if ($extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<'))
            <div class="absolute top-2.5 left-2.5">
            <span class="inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-amber-500 text-white shadow-lg animate-pulse" title="{{ __('extensions.settings.update_available') }}">
                <i class="bi bi-arrow-up-circle-fill mr-1"></i>{{ $extension->getLatestVersion() }}
            </span>
    </div>
    @endif

    @if ($tags->contains('slug', 'featured') || $tags->contains('slug', 'new') || $tags->contains('slug', 'popular'))
    <div class="absolute bottom-2.5 left-2.5">
        @if ($tags->contains('slug', 'featured'))
        <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow">
            <i class="bi bi-star-fill mr-0.5"></i>{{ __('extensions.settings.tags.featured') }}
        </span>
        @elseif ($tags->contains('slug', 'new'))
        <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-emerald-400 to-teal-500 text-white shadow">
            <i class="bi bi-newspaper mr-0.5"></i>{{ __('extensions.settings.tags.new') }}
        </span>
        @elseif ($tags->contains('slug', 'popular'))
        <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-rose-400 to-pink-500 text-white shadow">
            <i class="bi bi-fire mr-0.5"></i>{{ __('extensions.settings.tags.popular') }}
        </span>
        @endif
    </div>
    @endif
</div>

<div class="p-4">
    <div class="flex items-start justify-between gap-2 mb-1.5">
        <h3 class="font-bold text-gray-900 dark:text-white leading-tight line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-sm" title="{{ $extension->name() }}">
            {{ $extension->name() }}
        </h3>
        @if ($extension->isInstalled())
        <span class="flex-shrink-0 text-[10px] text-gray-500 dark:text-slate-400 font-mono bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $extension->version }}</span>
        @endif
    </div>

    <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-slate-400 mb-2">
        <span class="truncate">{{ $extension->author() }}</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $extension->price(true) }}</span>
        @if(isset($extension->api['reviews_count']) && $extension->api['reviews_count'] > 0)
        <div class="flex items-center gap-0.5">
            <i class="bi bi-star-fill text-yellow-500 text-xs"></i>
            <span class="text-xs text-gray-600 dark:text-slate-400">{{ number_format($extension->api['reviews_avg_rating'] ?? 0, 1) }}</span>
        </div>
        @endif
    </div>

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