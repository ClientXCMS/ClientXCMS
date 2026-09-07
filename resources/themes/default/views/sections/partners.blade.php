@php
$fallbackLogo = 'https://clientxcms.com/assets/images/logo/LogoBlueText.png';
$partners = section_config('partners', array_fill(0, 6, ['image' => $fallbackLogo, 'url' => '#', 'alt' => 'Partner']));
@endphp
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div class="max-w-2xl mx-auto text-center mb-10 lg:mb-14">
        <h2 class="text-2xl font-bold md:text-4xl dark:text-white">{{ section_config('title', 'Lorem') }}</h2>
        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ section_config('subtitle', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.') }}</p>
    </div>
    <div class="grid grid-cols-2 gap-8 md:grid-cols-4 lg:grid-cols-6">@foreach($partners as $partner)<a href="{{ $partner['url'] ?? '#' }}" class="flex justify-center items-center"><img src="{{ $partner['image'] ?? $fallbackLogo }}" alt="{{ $partner['alt'] ?? '' }}" class="h-10"></a>@endforeach</div>
</div>