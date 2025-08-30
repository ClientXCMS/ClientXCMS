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


namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * @OA\Schema(
 *     schema="UpdateGroupRequest",
 *
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string", maxLength=1000),
 *     @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}),
 *     @OA\Property(property="slug", type="string", maxLength=255),
 *     @OA\Property(property="sort_order", type="integer"),
 *     @OA\Property(property="pinned", type="boolean", nullable=true),
 *     @OA\Property(property="image", type="string", format="binary", nullable=true),
 *     @OA\Property(property="remove_image", type="string", enum={"true", "false"}, nullable=true),
 *     @OA\Property(property="parent_id", type="integer", nullable=true),
 *     @OA\Property(property="use_image_as_background", type="string", enum={"true", "false"}, nullable=true),
 * )
 */
class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [new RequiredIf(! $this->hasHeader('Authorization')), Rule::unique('groups', 'name')->ignore($this->route('group')->id), 'string', 'max:255'],
            'description' => [new RequiredIf(! $this->hasHeader('Authorization')), 'string', 'max:1000'],
            'status' => [new RequiredIf(! $this->hasHeader('Authorization')), 'string', 'in:active,hidden,unreferenced'],
            'slug' => [new RequiredIf(! $this->hasHeader('Authorization')), 'string', 'max:255', Rule::unique('groups', 'slug')->ignore($this->route('group')->id)],
            'sort_order' => [new RequiredIf(! $this->hasHeader('Authorization')), 'integer'],
            'parent_id' => ['nullable', 'integer', Rule::exists('groups', 'id')],
            'pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_image' => 'nullable|string|in:true,false',
            'use_image_as_background' => 'nullable|string|in:true,false',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'parent_id' => $this->parent_id == 'none' || $this->parent_id == null ? null : (int) $this->parent_id,
            'pinned' => $this->pinned == 'true' ? '1' : '0',
            'slug' => Str::slug($this->slug),
        ]);
    }

    public function update()
    {
        $validated = $this->validated();
        if ($this->file('image') != null) {
            if ($this->group->image != null) {
                \Storage::delete($this->group->image);
            }
            $filename = $this->group->slug.'.'.$this->file('image')->getClientOriginalExtension();
            $this->file('image')->storeAs('public'.DIRECTORY_SEPARATOR.'groups', $filename);
            $validated['image'] = 'groups/'.$filename;
        }
        if ($this->remove_image == 'true') {
            \Storage::delete($this->group->image);
            $validated['image'] = '';
        }
        if ($this->has('use_image_as_background')) {
            $this->group->attachMetadata('use_image_as_background', 'true');
        } else {
            $this->group->detachMetadata('use_image_as_background');
        }
        $this->group->update($validated);
    }
}
