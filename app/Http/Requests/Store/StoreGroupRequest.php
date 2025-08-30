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

use App\Models\Store\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="StoreGroupRequest",
 *     required={"name", "description", "status", "slug", "sort_order"},
 *
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}),
 *     @OA\Property(property="slug", type="string", maxLength=255),
 *     @OA\Property(property="sort_order", type="integer"),
 *     @OA\Property(property="pinned", type="boolean", nullable=true),
 *     @OA\Property(property="image", type="string", format="binary", nullable=true),
 *     @OA\Property(property="parent_id", type="integer", nullable=true),
 *     @OA\Property(property="use_image_as_background", type="boolean", nullable=true),
 * )
 */
class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|in:active,hidden,unreferenced',
            'slug' => 'required|string|max:255|unique:groups,slug',
            'sort_order' => 'required|integer',
            'pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'parent_id' => 'nullable|integer|exists:groups,id',
            'use_image_as_background' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'parent_id' => $this->parent_id == 'none' ? null : $this->parent_id,
            'pinned' => $this->pinned == 'true' ? '1' : '0',
            'slug' => Str::slug($this->slug),
        ]);
    }

    public function store()
    {
        $validated = $this->validated();
        if ($this->file('image') != null) {
            $filename = Str::slug($this->name).'.'.$this->file('image')->getClientOriginalExtension();
            $this->file('image')->storeAs('public'.DIRECTORY_SEPARATOR.'groups', $filename);
            $validated['image'] = 'groups/'.$filename;
        }
        $group = Group::create($validated);
        if ($this->has('use_image_as_background')) {
            $group->attachMetadata('use_image_as_background', 'true');
        }

        return $group;
    }
}
