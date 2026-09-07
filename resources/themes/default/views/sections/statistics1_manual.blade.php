@php
    $stats = collect(range(1, 4))->map(fn ($i) => ['icon' => section_config("stat{$i}_icon", 'bi-hdd-stack'), 'label' => section_config("stat{$i}_label", 'Lorem ipsum'), 'value' => section_config("stat{$i}_value", '0')]);
@endphp
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto"><div class="grid gap-6 grid-cols-2 sm:gap-12 lg:grid-cols-4 lg:gap-8">
    @foreach($stats as $stat)<div class="text-center"><div class="flex"><div class="inline-flex mx-auto justify-center items-center size-[62px] rounded-full border-4 border-blue-50 bg-blue-100 dark:border-blue-900 dark:bg-blue-800"><i class="bi {{ $stat['icon'] }} h-5 w-5 shrink-0 icon-width text-blue-600 dark:text-blue-400"></i></div></div><h4 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-neutral-200 mt-2">{{ $stat['label'] }}</h4><p class="mt-2 sm:mt-3 text-4xl sm:text-6xl font-bold text-blue-600">{{ $stat['value'] }}</p></div>@endforeach
</div></div>
