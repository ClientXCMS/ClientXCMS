{{-- Thumbnails are remote: when one cannot load, a neutral block replaces the broken image. --}}
<div class="relative">
    <img src="{{ $src }}" alt="" loading="lazy" class="{{ $class }}"
        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
    <div class="hidden {{ $class }} flex h-32 items-center justify-center bg-gray-100 text-gray-400 dark:bg-neutral-800 dark:text-neutral-500" aria-hidden="true">
        <i class="bi bi-image text-3xl"></i>
    </div>
</div>
