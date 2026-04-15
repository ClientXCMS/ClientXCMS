@php
$tags = collect($extension->api['tags'] ?? []);
$tagSlugs = $tags->pluck('slug')->implode(',');
@endphp
<div class="extension-item group flex items-center gap-4 p-4 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500 transition-all duration-200"
    data-category="{{ Str::slug($extension->api['group_uuid'] ?? 'unknown') }}"
    data-tags="{{ $tagSlugs }}"
    data-name="{{ $extension->name() }}"
    data-description="{{ $extension->api['short_description'] ?? '' }}">

    <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-slate-700 dark:to-slate-800 rounded-xl flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-200 {{ $extension->hasPadding() ? 'p-2' : '' }}">
        @if ($extension->thumbnail())
        <img src="{{ $extension->thumbnail() }}" class="max-h-full max-w-full object-contain" alt="{{ $extension->name() }}">
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
        @foreach ($tags->take(2) as $tag)
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400">
            <i class="{{ $tag['icon'] }} mr-1 text-xs"></i>{{ $tag['name'] }}
        </span>
        @endforeach
    </div>

    <div class="flex-shrink-0 text-right">
        <div class="font-bold text-gray-900 dark:text-white">{{ $extension->price(true) }}</div>
        @if($extension->isEnabled())
        <span class="text-xs text-green-600 dark:text-green-400">{{ __('extensions.settings.enabled') }}</span>
        @elseif($extension->isInstalled())
        <span class="text-xs text-blue-600 dark:text-blue-400">{{ __('extensions.settings.installed') }}</span>
        @endif
    </div>

    <div class="flex-shrink-0">
        @if ($extension->isNotInstalled() && $extension->isActivable())
        <form action="{{ route('admin.settings.extensions.update', [$extension->type(), $extension->uuid]) }}" method="POST" class="ajax-extension-form">
            @csrf
            <button class="p-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                <i class="bi bi-cloud-download"></i>
            </button>
        </form>
        @elseif (isset($extension->api['route']))
        <a href="{{ $extension->api['route'] }}" target="_blank" class="p-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors inline-block">
            <i class="bi bi-box-arrow-up-right"></i>
        </a>
        @endif
    </div>
</div>