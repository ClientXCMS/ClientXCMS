@php
    $descriptionItems = old('product_descriptions', $item->exists
        ? \App\Models\Store\Product::parseProductDescription($item->getMetadata('product_description'))
        : []);
    $descriptionLocales = \App\Services\Core\LocaleService::getLocales(true, true);
    $descriptionTranslations = old('product_description_translations', []);
    if ($item->exists && empty($descriptionTranslations)) {
        foreach ($descriptionLocales as $localeKey => $locale) {
            $value = json_decode($item->trans('product_description', '', $localeKey), true);
            $descriptionTranslations[$localeKey] = is_array($value) ? ($value['items'] ?? []) : [];
        }
    }
@endphp

<div id="product-description-overlay" class="hs-overlay hidden fixed inset-0 z-[90] overflow-y-auto bg-gray-900/50" tabindex="-1">
    <div class="mx-auto my-6 max-w-4xl rounded-xl bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b px-5 py-4 dark:border-gray-700">
            <div>
                <h3 class="font-bold text-gray-800 dark:text-white">{{ __('admin.products.short_description.title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.products.short_description.help') }}</p>
            </div>
            <button type="button" class="flex size-8 items-center justify-center rounded-full hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" data-hs-overlay="#product-description-overlay">
                <span class="sr-only">{{ __('global.closemodal') }}</span><i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-5">
            <input type="hidden" name="product_descriptions_present" value="1">
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" id="product-description-add" class="btn btn-primary"><i class="bi bi-plus-lg mr-2"></i>{{ __('admin.products.short_description.add') }}</button>
                <button type="button" id="product-description-import" class="btn btn-secondary"><i class="bi bi-arrow-down-square mr-2"></i>{{ __('admin.products.short_description.import') }}</button>
            </div>
            <div id="product-description-empty" class="rounded-lg border border-dashed p-5 text-center text-sm text-gray-500 dark:border-gray-600">{{ __('admin.products.short_description.empty') }}</div>
            <div id="product-description-list" class="space-y-3"></div>
            @error('product_descriptions.*.icon')<p class="mt-3 text-sm text-red-500">{{ $message }}</p>@enderror
            @error('product_descriptions.*.text')<p class="mt-3 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end border-t px-5 py-4 dark:border-gray-700">
            <button type="button" class="btn btn-primary" data-hs-overlay="#product-description-overlay">{{ __('global.close') }}</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('product-description-list');
    const empty = document.getElementById('product-description-empty');
    const locales = @json(collect($descriptionLocales)->map(fn ($locale) => $locale['name'])->all());
    const initialTranslations = @json($descriptionTranslations);
    const importLines = @json($item->formattedDescriptionLines());
    let items = @json(array_values($descriptionItems));
    let draggedIndex = null;

    const uuid = () => window.crypto?.randomUUID?.() || `description-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    const escapeAttribute = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const descriptionLines = () => {
        const liveEditor = document.querySelector('.editor-description .ql-editor');
        const html = liveEditor?.innerHTML || document.querySelector("textarea[name='description']")?.value || '';
        if (!html.trim()) return importLines;
        if (!/<[a-z][\s\S]*>/i.test(html)) return html.split(/\r?\n/).map(line => line.trim()).filter(Boolean);

        const documentNode = new DOMParser().parseFromString(html, 'text/html');
        const blocks = documentNode.body.querySelectorAll('p, li');
        const lines = Array.from(blocks).map(node => node.textContent.replace(/\s+/g, ' ').trim()).filter(Boolean);
        return lines.length ? lines : [documentNode.body.textContent.replace(/\s+/g, ' ').trim()].filter(Boolean);
    };

    function render() {
        list.innerHTML = '';
        empty.classList.toggle('hidden', items.length > 0);
        items.forEach((item, index) => {
            item.id ||= uuid();
            const translations = Object.fromEntries(Object.keys(locales).map(locale => [locale, item.translations?.[locale] ?? initialTranslations[locale]?.[item.id] ?? '']));
            const row = document.createElement('div');
            row.className = 'rounded-lg border p-4 dark:border-gray-700';
            row.draggable = true;
            row.innerHTML = `
                <div class="mb-3 flex items-center gap-3">
                    <button type="button" class="cursor-move text-gray-400" title="{{ __('admin.products.short_description.reorder') }}"><i class="bi bi-grip-vertical"></i></button>
                    <i class="product-description-preview text-xl ${escapeAttribute(item.icon)}"></i>
                    <input type="hidden" name="product_descriptions[${index}][id]" value="${escapeAttribute(item.id)}">
                    <input class="input-text flex-1 product-description-icon" name="product_descriptions[${index}][icon]" value="${escapeAttribute(item.icon)}" placeholder="bi bi-check-circle">
                    <button type="button" class="btn btn-danger product-description-remove"><i class="bi bi-trash"></i></button>
                </div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.products.short_description.main_text') }}</label>
                <input class="input-text mt-1 product-description-text" name="product_descriptions[${index}][text]" value="${escapeAttribute(item.text)}" maxlength="1000" required>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    ${Object.entries(locales).map(([locale, name]) => `
                        <div><label class="block text-xs text-gray-500">${escapeAttribute(name)}</label>
                        <input class="input-text mt-1 product-description-translation" data-locale="${escapeAttribute(locale)}" name="product_description_translations[${escapeAttribute(locale)}][${escapeAttribute(item.id)}]" value="${escapeAttribute(translations[locale])}" maxlength="1000"></div>
                    `).join('')}
                </div>`;
            row.addEventListener('dragstart', () => draggedIndex = index);
            row.addEventListener('dragover', event => event.preventDefault());
            row.addEventListener('drop', event => {
                event.preventDefault();
                if (draggedIndex === null || draggedIndex === index) return;
                const [moved] = items.splice(draggedIndex, 1);
                items.splice(index, 0, moved);
                render();
            });
            row.querySelector('.product-description-remove').addEventListener('click', () => { items.splice(index, 1); render(); });
            row.querySelector('.product-description-icon').addEventListener('input', event => {
                item.icon = event.target.value;
                row.querySelector('.product-description-preview').className = `product-description-preview text-xl ${item.icon}`;
            });
            row.querySelector('.product-description-text').addEventListener('input', event => item.text = event.target.value);
            row.querySelectorAll('.product-description-translation').forEach(input => input.addEventListener('input', event => {
                item.translations ||= {};
                item.translations[event.target.dataset.locale] = event.target.value;
            }));
            list.appendChild(row);
        });
    }

    document.getElementById('product-description-add').addEventListener('click', () => {
        items.push({id: uuid(), icon: '', text: '', translations: {}});
        render();
        list.lastElementChild?.querySelector('.product-description-text')?.focus();
    });
    document.getElementById('product-description-import').addEventListener('click', () => {
        if (items.length && !window.confirm(@json(__('admin.products.short_description.replace_confirm')))) return;
        items = descriptionLines().map(text => ({id: uuid(), icon: '', text, translations: {}}));
        render();
    });
    render();
});
</script>
