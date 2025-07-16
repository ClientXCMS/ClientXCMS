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
namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingsRequest extends FormRequest
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
            'app_name' => 'required|string|max:255',
            'app_env' => 'required|string|max:255',
            'app_debug' => 'required',
            'app_timezone' => 'required|string|max:255',
            'app_default_locale' => 'required|string|max:255',
            'app_logo' => 'nullable|mimes:jpeg,png,jpg|max:2048',
            'app_favicon' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'app_logo_text' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_app_logo' => 'nullable|string|in:true,false',
            'remove_app_favicon' => 'nullable|string|in:true,false',
            'remove_app_logo_text' => 'nullable|string|in:true,false',
            'app_telemetry' => 'nullable'
        ];
    }
}
