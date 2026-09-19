@php($menuItems = app('extension')->getAdminMenuItems())
@php($settingsCards = app('settings')->getCards())
@php($menuInstance = $menuInstance ?? 'vertical')

<ul class="hs-accordion-group flex flex-col gap-1" data-hs-accordion-always-open>
    @foreach ($menuItems as $item)
        @if ((empty($item->permission) || staff_has_permission($item->permission)) && $item->route !== 'admin.settings.index')
            @include('admin.layouts.menu.vertical-item', ['menuItem' => $item, 'menuInstance' => $menuInstance])
        @endif
    @endforeach

    @foreach ($settingsCards as $settingsCard)
        @php($availableItems = collect($settingsCard->items)->filter(fn ($settingItem) => $settingItem->isActive()))
        @continue($availableItems->isEmpty())
        @php($cardActive = $availableItems->contains(fn ($settingItem) => is_subroute($settingItem->url())))

        <li class="hs-accordion {{ $cardActive ? 'active' : '' }}" id="settings-{{ $settingsCard->uuid }}-{{ $menuInstance }}-accordion">
            <button type="button" class="hs-accordion-toggle flex w-full items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-start text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 hs-accordion-active:bg-indigo-50 hs-accordion-active:text-indigo-700 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-200 dark:hs-accordion-active:bg-neutral-800 dark:hs-accordion-active:text-indigo-200" aria-expanded="{{ $cardActive ? 'true' : 'false' }}" aria-controls="settings-{{ $settingsCard->uuid }}-{{ $menuInstance }}-accordion-child">
                <i class="{{ $settingsCard->icon }} size-4 shrink-0"></i>
                <span class="flex-1">{{ __($settingsCard->name) }}</span>
                <i class="bi bi-chevron-down size-4 hs-accordion-active:rotate-180"></i>
            </button>
            <div id="settings-{{ $settingsCard->uuid }}-{{ $menuInstance }}-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 {{ $cardActive ? '' : 'hidden' }}" role="region">
                <ul class="space-y-1 ps-6 pt-1">
                    @foreach ($availableItems as $settingItem)
                        <li>
                            <a href="{{ $settingItem->url() }}" @class([
                                'flex items-center gap-x-3.5 rounded-lg px-2.5 py-2 text-sm hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:hover:bg-gray-900 dark:focus:ring-1 dark:focus:ring-gray-600',
                                'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 dark:bg-neutral-800 dark:text-indigo-200 dark:ring-0' => is_subroute($settingItem->url()),
                                'text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => !is_subroute($settingItem->url()),
                            ])>
                                <i class="{{ $settingItem->icon }} size-4 shrink-0"></i>
                                <span>{{ __($settingItem->name) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @endforeach
</ul>
