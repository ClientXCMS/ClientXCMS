@php
$fieldKey = $field['key'];
$fieldType = $field['type'] ?? 'text';
$isTranslatable = $field['translatable'] ?? false;
$fieldValue = $values[$fieldKey] ?? $field['default'] ?? null;
$currentLocale = current_locale();

if ($isTranslatable && is_array($fieldValue)) {
$displayValue = $fieldValue[$currentLocale] ?? $fieldValue[array_key_first($fieldValue)] ?? '';
} else {
$displayValue = $fieldValue;
}
$fieldLabel = __($field['label'] ?? $fieldKey);
$fieldHelp = isset($field['hint']) ? __($field['hint']) : null;
$fieldName = $isTranslatable ? "{$fieldKey}[{$currentLocale}]" : $fieldKey;
@endphp

<div>
    @if($fieldType === 'text' || $fieldType === 'url')
    @include('admin/shared/input', [
    'label' => $fieldLabel,
    'name' => $fieldName,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'type' => $fieldType === 'url' ? 'url' : 'text',
    'translatable' => $isTranslatable,
    'translatableName' => $fieldKey,
    ])

    @elseif($fieldType === 'textarea')
    @include('admin/shared/textarea', [
    'label' => $fieldLabel,
    'name' => $fieldName,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'rows' => $field['rows'] ?? 3,
    'translatable' => $isTranslatable,
    ])

    @elseif($fieldType === 'number')
    @include('admin/shared/input', [
    'label' => $fieldLabel,
    'name' => $fieldName,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'type' => 'number',
    'min' => $field['min'] ?? null,
    'max' => $field['max'] ?? null,
    'step' => $field['step'] ?? null,
    ])

    @elseif($fieldType === 'boolean')
    @include('admin/shared/checkbox', [
    'label' => $fieldLabel,
    'name' => $fieldKey,
    'checked' => filter_var($displayValue, FILTER_VALIDATE_BOOLEAN),
    ])

    @elseif($fieldType === 'select')
    @php
    $selectOptions = [];
    foreach ($field['options'] ?? [] as $optKey => $optVal) {
    $key = is_numeric($optKey) ? $optVal : $optKey;
    $label = is_array($optVal) ? ($optVal['label'] ?? $key) : $optVal;
    $selectOptions[$key] = $label;
    }
    @endphp
    @include('admin/shared/select', [
    'label' => $fieldLabel,
    'name' => $fieldKey,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'options' => $selectOptions,
    ])

    @elseif($fieldType === 'color')
    @include('admin/shared/color', [
    'label' => $fieldLabel,
    'name' => $fieldKey,
    'value' => $displayValue,
    'help' => $fieldHelp,
    ])

    @elseif($fieldType === 'icon')
    @include('admin/shared/icon', [
    'label' => $fieldLabel,
    'name' => $fieldKey,
    'value' => $displayValue,
    'help' => $fieldHelp,
    ])

    @elseif($fieldType === 'image')
    <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-400 mt-2">
        {{ $fieldLabel }}
    </label>
    @if($displayValue)
    <div class="mt-2 flex items-start gap-3">
        <img src="{{ $displayValue }}" alt="" class="max-w-xs max-h-24 rounded border border-gray-200 dark:border-gray-700">
    </div>
    @endif
    @include('admin/shared/file', [
    'name' => $fieldKey,
    'help' => $fieldHelp,
    'canRemove' => $displayValue,
    'checked' => $displayValue,
    ])

    @elseif($fieldType === 'repeater')
    @include('admin/shared/repeater', [
    'label' => $fieldLabel,
    'name' => $fieldKey,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'fields' => $field['fields'] ?? $field['subfields'] ?? [],
    'min' => $field['min'] ?? 0,
    'max' => $field['max'] ?? 10,
    ])

    @else
    @include('admin/shared/input', [
    'label' => $fieldLabel,
    'name' => $fieldName,
    'value' => $displayValue,
    'help' => $fieldHelp,
    'translatable' => $isTranslatable,
    'translatableName' => $fieldKey,
    ])
    @endif
</div>