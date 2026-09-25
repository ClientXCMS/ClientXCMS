@php
    $menuInstance = $menuInstance ?? 'vertical';
    $children = collect($menuItem->children ?? [])->filter(fn ($child) => empty($child->permission) || staff_has_permission($child->permission));
    $hasChildren = $children->isNotEmpty();
    $isActive = !$hasChildren && is_subroute(route($menuItem->route, [], false));
    $isGroupActive = $hasChildren && $children->contains(function ($child) {
        if (empty($child->children)) {
            return is_subroute(route($child->route, [], false));
        }

        return collect($child->children)
            ->filter(fn ($grandChild) => empty($grandChild->permission) || staff_has_permission($grandChild->permission))
            ->contains(fn ($grandChild) => is_subroute(route($grandChild->route, [], false)));
    });
@endphp

<li @class(['hs-accordion' => $hasChildren, 'active' => $isGroupActive]) @if($hasChildren) id="{{ $menuItem->uuid }}-{{ $menuInstance }}-accordion" @endif>
    @if (!$hasChildren)
        <a href="{{ route($menuItem->route) }}" @class([
            'flex items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-sm hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:hover:bg-gray-900 dark:focus:ring-1 dark:focus:ring-gray-600',
            'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 dark:bg-neutral-800 dark:text-indigo-200 dark:ring-0' => $isActive,
            'text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => !$isActive,
        ])>
            @if (!empty($menuItem->icon))<i class="{{ $menuItem->icon }} size-4 shrink-0"></i>@endif
            <span>{{ __($menuItem->translation) }}</span>
        </a>
    @else
        <button type="button" class="hs-accordion-toggle flex w-full items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-start text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 hs-accordion-active:bg-indigo-50 hs-accordion-active:text-indigo-700 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-200 dark:hs-accordion-active:bg-neutral-800 dark:hs-accordion-active:text-indigo-200" aria-expanded="{{ $isGroupActive ? 'true' : 'false' }}" aria-controls="{{ $menuItem->uuid }}-{{ $menuInstance }}-accordion-child">
            @if (!empty($menuItem->icon))<i class="{{ $menuItem->icon }} size-4 shrink-0"></i>@endif
            <span class="flex-1">{{ __($menuItem->translation) }}</span>
            <i class="bi bi-chevron-down size-4 hs-accordion-active:rotate-180"></i>
        </button>
        <div id="{{ $menuItem->uuid }}-{{ $menuInstance }}-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 {{ $isGroupActive ? '' : 'hidden' }}" role="region">
            <ul class="space-y-1 ps-6 pt-1">
                @foreach ($children as $child)
                    @include('admin.layouts.menu.vertical-item', ['menuItem' => $child, 'menuInstance' => $menuInstance])
                @endforeach
            </ul>
        </div>
    @endif
</li>
