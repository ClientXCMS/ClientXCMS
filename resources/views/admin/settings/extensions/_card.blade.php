@php
$tags = collect($extension->api['tags'] ?? []);
$tagSlugs = $tags->pluck('slug')->implode(',');
$tagNames = $tags->pluck('name')->implode(',');
$hasUpdate = $extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<');
$isDeprecated = $tags->contains('slug', 'deprecated');
$authorData = $extension->api['author'] ?? [];
$authorAvatar = is_array($authorData) ? ($authorData['avatar'] ?? null) : null;
$reviewsCount = $extension->api['reviews_count'] ?? 0;
$reviewsAvg = $extension->api['reviews_avg_rating'] ?? 0;
$updatedAt = $extension->api['updated_at'] ?? null;
@endphp
<div class="js-extension-card extension-item group bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl overflow-hidden hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-500 hover:-translate-y-1 transition-all duration-300"
    role="article"
    aria-label="{{ __('extensions.settings.extension') ?? 'Extension' }} {{ $extension->name() }}"
    data-category="{{ Str::slug($groupName) }}"
    data-tags="{{ $tagSlugs }}"
    data-tag-names="{{ $tagNames }}"
    data-name="{{ $extension->name() }}"
    data-description="{{ $extension->description() }}"
    data-author="{{ $extension->author() }}"
    data-author-avatar="{{ $authorAvatar }}"
    data-uuid="{{ $extension->uuid }}"
    data-type="{{ $extension->type() }}"
    data-installed="{{ $extension->isInstalled() ? 'true' : 'false' }}"
    data-enabled="{{ $extension->isEnabled() ? 'true' : 'false' }}"
    data-activable="{{ $extension->isActivable() ? 'true' : 'false' }}"
    data-has-update="{{ $hasUpdate ? 'true' : 'false' }}"
    data-status="{{ $extension->isEnabled() ? 'enabled' : ($extension->isInstalled() ? 'installed' : 'not_installed') }}"
    data-thumbnail="{{ $extension->thumbnail() }}"
    data-version="{{ $extension->version }}"
    data-price="{{ $extension->price(true) }}"
    data-price-raw="{{ $extension->price(false) }}"
    data-rating="{{ $reviewsAvg }}"
    data-review-count="{{ $reviewsCount }}"
    data-updated-at="{{ $updatedAt }}"
    data-purchase-url="{{ $extension->api['route'] ?? '' }}"
    data-doc-url="{{ $extension->api['documentation_url'] ?? '' }}"
    data-enable-url="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}"
    data-disable-url="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}"
    data-install-url="{{ route('admin.settings.extensions.install', [$extension->type(), $extension->uuid]) }}"
    data-update-url="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}"
    data-unofficial="{{ $extension->isUnofficial() ? 'true' : 'false' }}"
    data-uninstall-url="{{ route('admin.settings.extensions.uninstall', [$extension->type(), $extension->uuid]) }}">

    {{-- Thumbnail: h-24 on desktop (lg+), h-40 on mobile --}}
    <div class="relative h-40 lg:h-24 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-800 dark:to-slate-900 flex items-center justify-center {{ $extension->hasPadding() ? 'p-4 lg:p-3' : '' }}">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300" alt="{{ $extension->name() }}" loading="lazy">
        @else
        <i class="bi bi-puzzle text-4xl lg:text-3xl text-gray-300 dark:text-slate-600"></i>
        @endif

        {{-- Status badge --}}
        <div class="absolute top-2.5 right-2.5">
            @if($extension->isEnabled())
            <span class="js-status-badge inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-green-500 text-white shadow-lg transition-colors duration-300">
                <i class="bi bi-check-circle-fill mr-1"></i><span class="js-status-text">{{ __('extensions.settings.enabled') }}</span>
            </span>
            @elseif($extension->isInstalled())
            <span class="js-status-badge inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-blue-500 text-white shadow-lg transition-colors duration-300">
                <i class="bi bi-check mr-1"></i><span class="js-status-text">{{ __('extensions.settings.installed') }}</span>
            </span>
            @else
            <span class="js-status-badge hidden inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium text-white shadow-lg transition-colors duration-300">
                <i class="mr-1"></i><span class="js-status-text"></span>
            </span>
            @endif
        </div>

        {{-- Update badge --}}
        @if ($hasUpdate)
        <div class="absolute top-2.5 left-2.5">
            <span class="js-update-badge inline-flex items-center py-1 px-2 rounded-lg text-xs font-medium bg-amber-500 text-white shadow-lg animate-pulse" title="{{ __('extensions.settings.update_available') }}">
                <i class="bi bi-arrow-up-circle-fill mr-1"></i>{{ $extension->getLatestVersion() }}
            </span>
        </div>
        @endif

        {{-- Tags & badges --}}
        <div class="absolute bottom-2.5 left-2.5 flex flex-wrap gap-1">
            @if ($tags->contains('slug', 'new'))
            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-emerald-400 to-teal-500 text-white shadow">
                <i class="bi bi-newspaper mr-0.5"></i>{{ __('extensions.settings.tags.new') ?? 'New' }}
            </span>
            @endif
            @if ($tags->contains('slug', 'popular'))
            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-rose-400 to-pink-500 text-white shadow">
                <i class="bi bi-fire mr-0.5"></i>{{ __('extensions.settings.tags.popular') ?? 'Popular' }}
            </span>
            @endif
            @if ($extension->isUnofficial())
            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gray-400 text-white shadow">
                <i class="bi bi-person-badge mr-0.5"></i>{{ __('extensions.settings.unofficial') ?? 'Unofficial' }}
            </span>
            @endif
            @if ($isDeprecated)
            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-red-500 text-white shadow">
                <i class="bi bi-exclamation-triangle mr-0.5"></i>{{ __('extensions.settings.deprecated') ?? 'Deprecated' }}
            </span>
            @endif
        </div>
    </div>

    {{-- Content: p-5 on mobile, p-4 on desktop --}}
    <div class="p-5 lg:p-4">
        <div class="flex items-start justify-between gap-2 mb-1.5">
            <h3 class="font-bold text-gray-900 dark:text-white leading-tight line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-sm" title="{{ $extension->name() }}">
                {{ $extension->name() }}
            </h3>
            @if ($extension->isInstalled())
            <span class="flex-shrink-0 text-[10px] text-gray-500 dark:text-slate-400 font-mono bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">v{{ $extension->version }}</span>
            @endif
        </div>

        {{-- Author + avatar + metadata --}}
        <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-slate-400 mb-2">
            @if ($authorAvatar)
            <img src="{{ $authorAvatar }}" alt="{{ $extension->author() }}" class="w-4 h-4 rounded-full hidden lg:inline-block">
            @endif
            <span class="truncate">{{ $extension->author() }}</span>
            @if ($updatedAt)
            <span class="hidden lg:inline-block text-gray-400 dark:text-slate-500">&middot;</span>
            <span class="hidden lg:inline-block text-gray-400 dark:text-slate-500">{{ \Carbon\Carbon::parse($updatedAt)->diffForHumans() }}</span>
            @endif
        </div>

        {{-- Price + Rating --}}
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $extension->price(true) }}</span>
            @if($reviewsCount > 0)
            <div class="flex items-center gap-0.5">
                <i class="bi bi-star-fill text-yellow-500 text-xs"></i>
                <span class="text-xs text-gray-600 dark:text-slate-400">{{ number_format($reviewsAvg, 1) }}</span>
                <span class="text-xs text-gray-400 dark:text-slate-500 hidden lg:inline">({{ $reviewsCount }})</span>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="js-card-actions flex flex-col gap-2">
            @if ($hasUpdate)
            <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-update w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-download"></i>{{ __('extensions.settings.update') }}
                </button>
            </form>
            @endif

            @if ($extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-disable w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-x-circle"></i>{{ __('extensions.settings.disable') }}
                </button>
            </form>
            @elseif ($extension->isInstalled() && !$extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-enable w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-check-circle"></i>{{ __('extensions.settings.enable') }}
                </button>
            </form>
            @if (!$extension->isUnofficial())
            <form action="{{ route('admin.settings.extensions.uninstall', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-uninstall w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-gray-200 dark:bg-slate-700 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-600 dark:text-gray-400 rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-trash"></i>{{ __('extensions.settings.uninstall') }}
                </button>
            </form>
            @endif
            @elseif ($extension->isNotInstalled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.install', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-install w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-cloud-download"></i>{{ __('extensions.settings.install') }}
                </button>
            </form>
            @else
            <a class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0" href="{{ $extension->api['route'] ?? '#' }}" target="_blank">
                <i class="bi bi-bag"></i>{{ __('extensions.settings.buy') }}
            </a>
            @endif

            <button type="button" class="js-btn-details w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                <i class="bi bi-info-circle"></i>{{ __('global.details') }}
            </button>
        </div>
    </div>
</div>
