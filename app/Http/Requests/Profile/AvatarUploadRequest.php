<?php

namespace App\Http\Requests\Profile;

use App\Services\Account\AvatarService;
use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null || $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.AvatarService::MAX_KILOBYTES],
        ];
    }
}
