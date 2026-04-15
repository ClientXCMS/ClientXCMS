<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\DTO\Core\Extensions;

class SectionField
{
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public bool $translatable = false,
        public ?string $hint = null,
        public $default = null,
        public ?int $rows = null,
        public ?array $fields = null,
        public ?int $min = null,
        public ?int $max = null,
        public ?float $step = null,
        public ?array $options = null,
    ) {}

    /**
     * Create a text field.
     */
    public static function text(string $key, string $label, bool $translatable = true, ?string $hint = null, ?string $default = null): self
    {
        return new self(
            key: $key,
            type: 'text',
            label: $label,
            translatable: $translatable,
            hint: $hint,
            default: $default,
        );
    }

    public static function groupText(int $count, string $key, string $label, bool $translatable = true, ?string $hint = null, ?string $default = null, ?string $replace = 'x'): array
    {
        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            $fields[] = self::text(
                key: str_replace($replace, $i, $key),
                label: str_replace($replace, $i, $label),
                translatable: $translatable,
                hint: $hint,
                default: $default,
            );
        }

        return $fields;
    }

    /**
     * Create a textarea field.
     */
    public static function textarea(string $key, string $label, int $rows = 2, bool $translatable = true, ?string $hint = null, ?string $default = null): self
    {
        return new self(
            key: $key,
            type: 'textarea',
            label: $label,
            translatable: $translatable,
            hint: $hint,
            rows: $rows,
            default: $default,
        );
    }

    /**
     * Create an icon picker field.
     */
    public static function icon(string $key, string $label, string $default = 'bi-star'): self
    {
        return new self(
            key: $key,
            type: 'icon',
            label: $label,
            default: $default,
        );
    }

    /**
     * Create a number field.
     */
    public static function number(string $key, string $label, ?int $min = null, ?int $max = null, ?float $step = null, ?string $hint = null, ?string $default = null): self
    {
        return new self(
            key: $key,
            type: 'number',
            label: $label,
            hint: $hint,
            default: $default,
            min: $min,
            max: $max,
            step: $step,
        );
    }

    /**
     * Create a boolean field.
     */
    public static function boolean(string $key, string $label, bool $default = false, ?string $hint = null): self
    {
        return new self(
            key: $key,
            type: 'boolean',
            label: $label,
            hint: $hint,
            default: $default ? '1' : '0',
        );
    }

    /**
     * Create a select field.
     */
    public static function select(string $key, string $label, array $options, ?string $default = null, ?string $hint = null): self
    {
        return new self(
            key: $key,
            type: 'select',
            label: $label,
            hint: $hint,
            default: $default,
            options: $options,
        );
    }

    /**
     * Create a color picker field.
     */
    public static function color(string $key, string $label, ?string $default = null, ?string $hint = null): self
    {
        return new self(
            key: $key,
            type: 'color',
            label: $label,
            hint: $hint,
            default: $default,
        );
    }

    /**
     * Create a URL field.
     */
    public static function url(string $key, string $label, bool $translatable = true, ?string $hint = null, ?string $default = null): self
    {
        return new self(
            key: $key,
            type: 'url',
            label: $label,
            translatable: $translatable,
            hint: $hint,
            default: $default,
        );
    }

    /**
     * Create an image field.
     */
    public static function image(string $key, string $label, ?string $hint = null): self
    {
        return new self(
            key: $key,
            type: 'image',
            label: $label,
            hint: $hint,
        );
    }

    /**
     * Create a repeater field.
     */
    public static function repeater(string $key, string $label, array $fields, int $min = 0, int $max = 10, ?string $hint = null): self
    {
        return new self(
            key: $key,
            type: 'repeater',
            label: $label,
            hint: $hint,
            fields: array_map(fn ($field) => $field instanceof self ? $field->toArray() : $field, $fields),
            min: $min,
            max: $max,
        );
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Convert to the array format expected by ThemeSectionDTO.
     */
    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'type' => $this->type,
        ];

        if ($this->translatable) {
            $data['translatable'] = true;
        }

        $data['label'] = $this->label;

        if ($this->hint !== null) {
            $data['hint'] = $this->hint;
        }

        if ($this->default !== null) {
            $data['default'] = $this->default;
        }

        if ($this->rows !== null) {
            $data['rows'] = $this->rows;
        }

        if ($this->fields !== null) {
            $data['fields'] = $this->fields;
        }

        if ($this->min !== null) {
            $data['min'] = $this->min;
        }

        if ($this->max !== null) {
            $data['max'] = $this->max;
        }

        if ($this->step !== null) {
            $data['step'] = $this->step;
        }

        if ($this->options !== null) {
            $data['options'] = $this->options;
        }

        return $data;
    }
}
