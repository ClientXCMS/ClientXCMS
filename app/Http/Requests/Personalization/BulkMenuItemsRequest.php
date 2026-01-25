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

namespace App\Http\Requests\Personalization;

use Illuminate\Foundation\Http\FormRequest;

class BulkMenuItemsRequest extends FormRequest
{
    private const MAX_DEPTH = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->buildRulesForDepth(self::MAX_DEPTH);
    }

    /**
     * Build validation rules recursively for each nesting level.
     * Supports up to 3 levels: items, items.*.children, items.*.children.*.children
     */
    private function buildRulesForDepth(int $maxDepth): array
    {
        $rules = [
            'items' => 'required|array',
        ];

        $prefixes = ['items.*'];

        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            foreach ($prefixes as $prefix) {
                $rules = array_merge($rules, $this->getItemRules($prefix));
            }

            if ($depth < $maxDepth) {
                $prefixes = array_map(fn($p) => $p . '.children.*', $prefixes);
            }
        }

        return $rules;
    }

    /**
     * Validation rules for a single menu item at any depth.
     */
    private function getItemRules(string $prefix): array
    {
        return [
            $prefix . '.id' => 'nullable|integer',
            $prefix . '.name' => 'required|string|max:255',
            $prefix . '.url' => 'required|string|max:255',
            $prefix . '.icon' => 'nullable|string|max:255',
            $prefix . '.badge' => 'nullable|string|max:255',
            $prefix . '.description' => 'nullable|string|max:255',
            $prefix . '.link_type' => 'required|string|in:link,new_tab,dropdown',
            $prefix . '.allowed_role' => 'required|string|in:all,staff,customer,logged',
            $prefix . '.isDeleted' => 'boolean',
            $prefix . '.children' => 'nullable|array',
            $prefix . '.translations' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => __('validation.required', ['attribute' => 'items']),
            'items.*.name.required' => __('validation.required', ['attribute' => 'name']),
            'items.*.url.required' => __('validation.required', ['attribute' => 'url']),
            'items.*.link_type.required' => __('validation.required', ['attribute' => 'link_type']),
            'items.*.allowed_role.required' => __('validation.required', ['attribute' => 'allowed_role']),
        ];
    }
}
