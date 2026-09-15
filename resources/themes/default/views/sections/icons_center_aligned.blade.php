@php
    $features = collect(range(1, 4))->map(fn ($i) => [
        'icon' => section_config("feature{$i}_icon", 'bi-hdd-stack'),
        'title' => section_config("feature{$i}_title", 'Lorem Ipsum'),
        'description' => section_config("feature{$i}_description", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'),
    ]);
@endphp
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto mt-3.5">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 items-center gap-12">
        @foreach($features as $feature)
            <div class="text-center"><div class="flex justify-center items-center w-12 h-12 bg-gray-50 border border-gray-200 rounded-full mx-auto dark:bg-gray-800 dark:border-gray-700"><i class="bi {{ $feature['icon'] }} w-5 h-5 text-gray-600 dark:text-gray-400"></i></div><div class="mt-3"><h3 class="text-lg font-semibold text-gray-800 dark:text-white">{{ $feature['title'] }}</h3><p class="mt-1 text-gray-600 dark:text-gray-400">{{ $feature['description'] }}</p></div></div>
        @endforeach
    </div>
</div>
