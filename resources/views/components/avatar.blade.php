@props([
    'user' => null,
    'size' => 'md',  // sm | md | lg | xl
    'alt' => null,
])

@php
    use App\Services\Account\AvatarService;

    $sizeMap = [
        'sm' => 'h-6 w-6 text-[10px]',
        'md' => 'h-9 w-9 text-xs',
        'lg' => 'h-12 w-12 text-sm',
        'xl' => 'h-20 w-20 text-base',
    ];
    $sizeClasses = $sizeMap[$size] ?? $sizeMap['md'];

    $url = AvatarService::url($user);
    $initials = AvatarService::initials($user);
    $background = AvatarService::backgroundColour($user);
    $label = $alt ?? trim((string) ($user?->firstname . ' ' . $user?->lastname)) ?: ($user?->email ?? '');
@endphp

@if($url)
    <img
        src="{{ $url }}"
        alt="{{ $label }}"
        loading="lazy"
        {{ $attributes->merge(['class' => $sizeClasses . ' rounded-full object-cover ring-2 ring-white dark:ring-gray-800']) }}
    >
@else
    <span
        role="img"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => $sizeClasses . ' inline-flex items-center justify-center rounded-full font-semibold text-white leading-none ring-2 ring-white dark:ring-gray-800']) }}
        style="background-color: {{ $background }};"
    >
        {{ $initials }}
    </span>
@endif
