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
 * Year: 2025
 */
namespace App\Http\Requests\Personalization;

use Illuminate\Foundation\Http\FormRequest;

class MenuLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
            'url' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:255',
            'link_type' => 'required|string',
            'allowed_role' => 'required|string',
            'parent_id' => 'nullable',
            'description' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->get('parent_id') === 'none') {
            $this->merge(['parent_id' => null]);
        }
        if ($this->get('name') === null) {
            $this->merge(['name' => '']);
        }
    }
}
