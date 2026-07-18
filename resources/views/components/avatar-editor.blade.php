@props(['user', 'uploadRoute', 'deleteRoute', 'inputId' => 'avatar', 'variant' => 'bar'])

<div {{ $attributes->class([
    'flex items-center gap-3',
    'mb-4 w-full border-b border-gray-200 pb-4 dark:border-gray-700' => $variant === 'bar',
    'min-h-20 py-1' => $variant === 'field',
]) }}>
    <x-avatar :user="$user" :size="$variant === 'field' ? 'md' : 'lg'" />
    <div class="min-w-0 flex-1">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('client.profile.avatar.title') }}</h3>
        <p class="hidden text-xs text-gray-500 dark:text-gray-400 {{ $variant === 'bar' ? 'truncate sm:block' : 'xl:block' }}">{{ __('client.profile.avatar.description') }}</p>
        @error('avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data">
            @csrf
            <label for="{{ $inputId }}" title="{{ $user->avatar_path ? __('client.profile.avatar.replace') : __('client.profile.avatar.upload') }}" class="inline-flex min-h-9 cursor-pointer items-center justify-center gap-1.5 rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 sm:px-3 sm:text-sm {{ $variant === 'field' ? 'size-9 !p-0' : '' }}">
                <i class="bi bi-camera" aria-hidden="true"></i>
                <span class="{{ $variant === 'field' ? 'sr-only' : '' }}">
                    {{ $user->avatar_path ? __('client.profile.avatar.replace') : __('client.profile.avatar.upload') }}
                </span>
            </label>
            <input id="{{ $inputId }}" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" onchange="this.form.submit()">
        </form>
        @if ($user->avatar_path)
            <form method="POST" action="{{ $deleteRoute }}">
                @csrf
                @method('DELETE')
                <button type="submit" title="{{ __('client.profile.avatar.remove') }}" class="inline-flex size-9 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-red-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-red-400">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span class="sr-only">{{ __('client.profile.avatar.remove') }}</span>
                </button>
            </form>
        @endif
    </div>
</div>
