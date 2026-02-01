@php
$tags = collect($extension->api['tags'] ?? []);
$tagSlugs = $tags->pluck('slug')->implode(',');
$tagNames = $tags->pluck('name')->implode(',');
$hasUpdate = $extension->isInstalled() && $extension->getLatestVersion() && version_compare($extension->version, $extension->getLatestVersion(), '<');
$isDeprecated = $tags->contains('slug', 'deprecated');
$reviewsCount = $extension->api['reviews_count'] ?? 0;
$reviewsAvg = $extension->api['reviews_avg_rating'] ?? 0;
$updatedAt = $extension->api['updated_at'] ?? null;
$authorData = $extension->api['author'] ?? [];
$authorAvatar = is_array($authorData) ? ($authorData['avatar'] ?? null) : null;
@endphp
<div class="js-extension-card extension-item group bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-500 hover:-translate-y-1 transition-all duration-300"
    role="article"
    aria-label="{{ __('extensions.settings.extension') ?? 'Extension' }} {{ $extension->name() }}"
    data-category="themes"
    data-tags="{{ $tagSlugs }}"
    data-tag-names="{{ $tagNames }}"
    data-name="{{ $extension->name() }}"
    data-description="{{ $extension->api['short_description'] ?? '' }}"
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

    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-800 dark:to-slate-900 overflow-hidden">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="{{ $extension->name() }}" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center">
            <i class="bi bi-palette text-5xl text-gray-300 dark:text-slate-600"></i>
        </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-6">
            <button type="button" class="js-btn-details px-4 py-2.5 bg-white/90 text-gray-900 rounded-lg font-medium text-sm hover:bg-white transition-colors min-h-[44px]">
                <i class="bi bi-eye mr-2"></i>{{ __('global.details') }}
            </button>
        </div>

        {{-- Status badge --}}
        <div class="absolute top-3 right-3">
            @if($extension->isEnabled())
            <span class="js-status-badge inline-flex items-center py-1.5 px-3 rounded-lg text-xs font-medium bg-green-500 text-white shadow-lg transition-colors duration-300">
                <i class="bi bi-check-circle-fill mr-1"></i><span class="js-status-text">{{ __('extensions.settings.active') }}</span>
            </span>
            @elseif($extension->isInstalled())
            <span class="js-status-badge inline-flex items-center py-1.5 px-3 rounded-lg text-xs font-medium bg-blue-500 text-white shadow-lg transition-colors duration-300">
                <i class="bi bi-check mr-1"></i><span class="js-status-text">{{ __('extensions.settings.installed') }}</span>
            </span>
            @endif
        </div>

        {{-- Update badge --}}
        @if ($hasUpdate)
        <div class="absolute top-3 left-3">
            <span class="js-update-badge inline-flex items-center py-1.5 px-2.5 rounded-lg text-xs font-medium bg-amber-500 text-white shadow-lg animate-pulse">
                <i class="bi bi-arrow-up-circle-fill mr-1"></i>{{ $extension->getLatestVersion() }}
            </span>
        </div>
        @endif

        {{-- Tag + badge overlays --}}
        <div class="absolute bottom-3 left-3 flex flex-wrap gap-1">
            @if ($tags->contains('slug', 'new'))
            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gradient-to-r from-emerald-400 to-teal-500 text-white shadow">
                <i class="bi bi-newspaper mr-0.5"></i>{{ __('extensions.settings.tags.new') ?? 'New' }}
            </span>
            @endif
            @if ($tags->contains('slug', 'popular'))
            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gradient-to-r from-rose-400 to-pink-500 text-white shadow">
                <i class="bi bi-fire mr-0.5"></i>{{ __('extensions.settings.tags.popular') ?? 'Popular' }}
            </span>
            @endif
            @if ($extension->isUnofficial())
            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gray-400 text-white shadow">
                <i class="bi bi-person-badge mr-0.5"></i>{{ __('extensions.settings.unofficial') ?? 'Unofficial' }}
            </span>
            @endif
            @if ($isDeprecated)
            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-red-500 text-white shadow">
                <i class="bi bi-exclamation-triangle mr-0.5"></i>{{ __('extensions.settings.deprecated') ?? 'Deprecated' }}
            </span>
            @endif
        </div>
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

        <div class="js-card-actions flex flex-col gap-2">
            @if ($hasUpdate)
            <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-update w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-download"></i>{{ __('extensions.settings.update') }}
                </button>
            </form>
            @endif

            @if ($extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-disable w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-x-circle"></i>{{ __('extensions.settings.disable') }}
                </button>
            </form>
            @elseif ($extension->isInstalled() && !$extension->isEnabled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-enable w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-check-circle"></i>{{ __('extensions.settings.enable') }}
                </button>
            </form>
            @if (!$extension->isUnofficial())
            <form action="{{ route('admin.settings.extensions.uninstall', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-uninstall w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-gray-200 dark:bg-slate-700 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-600 dark:text-gray-400 rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-trash"></i>{{ __('extensions.settings.uninstall') }}
                </button>
            </form>
            @endif
            @elseif ($extension->isNotInstalled() && $extension->isActivable())
            <form action="{{ route('admin.settings.extensions.install', [$extension->type(), $extension->uuid]) }}" method="POST">
                @csrf
                <button type="submit" class="js-btn-install w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0">
                    <i class="bi bi-cloud-download"></i>{{ __('extensions.settings.install') }}
                </button>
            </form>
            @else
            <a class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-3.5 lg:py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-sm transition-colors min-h-[44px] lg:min-h-0" href="{{ $extension->api['route'] ?? '#' }}" target="_blank">
                <i class="bi bi-bag"></i>{{ __('extensions.settings.buy') }}
            </a>
            @endif
        </div>
    </div>
</div>
