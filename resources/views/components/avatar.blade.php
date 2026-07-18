@props(['user' => null, 'size' => 'md', 'alt' => null])

@php
    $avatar = app(\App\Services\Account\AvatarService::class);
    $sizes = ['sm' => 'h-6 w-6 text-[10px]', 'md' => 'h-9 w-9 text-xs', 'lg' => 'h-12 w-12 text-sm', 'xl' => 'h-20 w-20 text-base'];
    $classes = ($sizes[$size] ?? $sizes['md']).' shrink-0 rounded-full ring-2 ring-white dark:ring-gray-800';
    $label = $alt ?? trim(($user?->firstname ?? '').' '.($user?->lastname ?? '')) ?: ($user?->email ?? '');
@endphp

@if ($url = $avatar->url($user))
    <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" {{ $attributes->class([$classes, 'object-cover']) }}>
@else
    <span role="img" aria-label="{{ $label }}" style="background-color: {{ $avatar->backgroundColor($user) }}" {{ $attributes->class([$classes, 'inline-flex items-center justify-center font-semibold leading-none text-white']) }}>
        {{ $avatar->initials($user) }}
    </span>
@endif
