@php
    $cards = collect(range(1, 2))->map(fn ($i) => [
        'title' => section_config("card{$i}_title", 'Lorem ipsum dolor'),
        'description' => section_config("card{$i}_description", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit'),
        'icon' => section_config("card{$i}_icon", 'bi-hdd-stack'),
        'url' => section_config("card{$i}_url", '#'),
    ]);
@endphp
<div class="max-w-5xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div class="grid sm:grid-cols-2 gap-3 sm:gap-6">
        @foreach($cards as $card)
            <a class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="{{ $card['url'] }}">
                <div class="p-4 md:p-5"><div class="flex gap-x-5"><i class="mt-1 shrink-0 size-5 text-gray-800 dark:text-neutral-200 bi {{ $card['icon'] }} icon-width"></i><div class="grow"><h3 class="group-hover:text-blue-600 font-semibold text-gray-800 dark:text-neutral-200">{{ $card['title'] }}</h3><p class="text-sm text-gray-500 dark:text-neutral-500">{{ $card['description'] }}</p></div></div></div>
            </a>
        @endforeach
    </div>
</div>
