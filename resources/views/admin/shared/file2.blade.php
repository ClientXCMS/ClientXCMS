<?php
/*
 * This file is part of the CLIENTXCMS project.
 */
?>
@php($id = ($name ?? 'file').rand(100,999))
@if(isset($label))
    <label for="{{ $id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">{{ $label }}</label>
@endif
<label for="{{ $id }}" class="mt-2 flex flex-col items-center justify-center w-full min-h-[110px] border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 dark:bg-slate-900 dark:border-slate-700 dark:hover:bg-slate-800 transition">
    <div class="flex flex-col items-center justify-center pt-4 pb-4 px-4 text-center">
        <i class="bi bi-cloud-arrow-up text-xl text-gray-500"></i>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('global.clicktoupload') ?? 'Cliquez ou glissez-déposez vos fichiers' }}</p>
        <p class="text-xs text-gray-500">{{ $help ?? '' }}</p>
    </div>
    <input id="{{ $id }}" type="file" class="hidden" name="{{ $name }}[]" @if(!isset($multiple) || $multiple) multiple @endif />
</label>
<ul id="{{ $id }}-list" class="mt-2 text-xs text-gray-600 dark:text-gray-300 space-y-1"></ul>
@error($name)
<span class="mt-2 text-sm text-red-500">{{ $message }}</span>
@enderror
@for ($i = 0; $i < 6; $i++)
    @error("attachments.$i")
        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
    @enderror
@endfor
<script>
(function(){
    const input = document.getElementById(@json($id));
    const list = document.getElementById(@json($id.'-list'));
    if (!input || !list) return;
    const update = () => {
        list.innerHTML = '';
        Array.from(input.files || []).forEach(f => {
            const li = document.createElement('li');
            li.textContent = `• ${f.name} (${Math.round(f.size / 1024)} KB)`;
            list.appendChild(li);
        });
    };
    input.addEventListener('change', update);
})();
</script>
