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
<div class="js-extension-card extension-item group flex items-center gap-4 p-4 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-500 hover:-translate-y-1 transition-all duration-300"
    role="article"
    aria-label="{{ __('extensions.settings.extension') ?? 'Extension' }} {{ $extension->name() }}"
    data-category="{{ Str::slug($extension->api['group_uuid'] ?? 'unknown') }}"
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
    data-update-url="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}">

    <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-700 dark:to-slate-800 rounded-xl flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-200 {{ $extension->hasPadding() ? 'p-2' : '' }}">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="max-h-full max-w-full object-contain" alt="{{ $extension->name() }}" loading="lazy">
        @else
        <i class="bi bi-puzzle text-xl text-gray-300 dark:text-slate-600"></i>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
            {{ $extension->name() }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $extension->author() }}</p>
    </div>

    <div class="flex-shrink-0 hidden sm:flex items-center gap-1">
        @if ($tags->contains('slug', 'new'))
        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-gradient-to-r from-emerald-400 to-teal-500 text-white shadow">
            <i class="bi bi-newspaper mr-0.5"></i>{{ __('extensions.settings.tags.new') ?? 'New' }}
        </span>
        @endif
        @if ($tags->contains('slug', 'popular'))
        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-gradient-to-r from-rose-400 to-pink-500 text-white shadow">
            <i class="bi bi-fire mr-0.5"></i>{{ __('extensions.settings.tags.popular') ?? 'Popular' }}
        </span>
        @endif
        @if ($extension->isUnofficial())
        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-gray-400 text-white shadow">
            <i class="bi bi-person-badge mr-0.5"></i>{{ __('extensions.settings.unofficial') ?? 'Unofficial' }}
        </span>
        @endif
        @if ($isDeprecated)
        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-red-500 text-white shadow">
            <i class="bi bi-exclamation-triangle mr-0.5"></i>{{ __('extensions.settings.deprecated') ?? 'Deprecated' }}
        </span>
        @endif
    </div>

    <div class="flex-shrink-0 text-right">
        <div class="font-bold text-gray-900 dark:text-white">{{ $extension->price(true) }}</div>
        @if($extension->isEnabled())
        <span class="js-status-badge inline-flex items-center text-xs text-green-600 dark:text-green-400">
            <i class="bi bi-check-circle-fill mr-0.5 text-[10px]"></i><span class="js-status-text">{{ __('extensions.settings.enabled') }}</span>
        </span>
        @elseif($extension->isInstalled())
        <span class="js-status-badge inline-flex items-center text-xs text-blue-600 dark:text-blue-400">
            <i class="bi bi-check mr-0.5 text-[10px]"></i><span class="js-status-text">{{ __('extensions.settings.installed') }}</span>
        </span>
        @endif
    </div>

    <div class="js-card-actions flex-shrink-0 flex items-center gap-2">
        @if ($hasUpdate)
        <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST">
            @csrf
            <button type="submit" class="js-btn-update p-2.5 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" title="{{ __('extensions.settings.update') }}">
                <i class="bi bi-download"></i>
            </button>
        </form>
        @endif
        @if ($extension->isEnabled() && $extension->isActivable())
        <form action="{{ route('admin.settings.extensions.disable', [$extension->type(), $extension->uuid]) }}" method="POST">
            @csrf
            <button type="submit" class="js-btn-disable p-2.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" title="{{ __('extensions.settings.disable') }}">
                <i class="bi bi-x-circle"></i>
            </button>
        </form>
        @elseif ($extension->isInstalled() && !$extension->isEnabled() && $extension->isActivable())
        <form action="{{ route('admin.settings.extensions.enable', [$extension->type(), $extension->uuid]) }}" method="POST">
            @csrf
            <button type="submit" class="js-btn-enable p-2.5 rounded-lg bg-green-500 text-white hover:bg-green-600 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" title="{{ __('extensions.settings.enable') }}">
                <i class="bi bi-check-circle"></i>
            </button>
        </form>
        @elseif ($extension->isNotInstalled() && $extension->isActivable())
        <form action="{{ route('admin.settings.extensions.install', [$extension->type(), $extension->uuid]) }}" method="POST">
            @csrf
            <button type="submit" class="js-btn-install p-2.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" title="{{ __('extensions.settings.install') }}">
                <i class="bi bi-cloud-download"></i>
            </button>
        </form>
        @endif
        <button type="button" class="js-btn-details p-2.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" title="{{ __('global.details') }}">
            <i class="bi bi-info-circle"></i>
        </button>
    </div>
</div>
