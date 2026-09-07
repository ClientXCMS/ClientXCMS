@php
    $fallbackLogo = 'https://clientxcms.com/assets/images/logo/LogoBlueText.png';
    $partners = section_config('partners', array_fill(0, 4, ['image' => $fallbackLogo, 'url' => '#', 'alt' => 'Partner']));
@endphp
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto"><div class="w-2/3 sm:w-1/2 lg:w-1/3 mx-auto text-center mb-6"><h2 class="text-gray-600 dark:text-neutral-400">{{ section_config('title', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit') }}</h2>@if(section_config('subtitle'))<p>{{ section_config('subtitle') }}</p>@endif</div><div class="flex flex-wrap justify-center gap-x-6 sm:gap-x-12 lg:gap-x-24">@foreach($partners as $partner)<a href="{{ $partner['url'] ?? '#' }}"><img src="{{ $partner['image'] ?? $fallbackLogo }}" alt="{{ $partner['alt'] ?? '' }}" class="lg:py-5 w-24 h-20 lg:w-32 mx-auto sm:mx-0"></a>@endforeach</div></div>
