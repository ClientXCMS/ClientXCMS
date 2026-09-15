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

use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    use PricingRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Web admin context: enforce capability so a low-priv staff cannot
        // even reach validation. API context (Sanctum token) is gated by
        // the route's ability:products:store middleware, so we let the
        // request through here.
        if (auth('admin')->check()) {
            return staff_has_permission(\App\Models\Admin\Permission::MANAGE_PRODUCTS);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = app('extension')->getProductTypes()->keys()->merge(['none'])->toArray();

        return array_merge([
            'name' => 'required|string|max:255',
            'description' => ['string', 'required', 'max:65535', new \App\Rules\NoScriptOrPhpTags],
            'status' => 'required|string|in:active,hidden,unreferenced',
            'group_id' => 'required|integer|exists:groups,id',
            'stock' => 'required|integer',
            'type' => ['required', 'string', Rule::in($types)],
            'pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_descriptions' => 'nullable|array|max:100',
            'product_descriptions_present' => 'nullable|boolean',
            'product_descriptions.*.id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'product_descriptions.*.text' => 'required_with:product_descriptions|string|max:1000',
            'product_descriptions.*.icon' => ['nullable', 'string', 'max:100', 'regex:/^bi bi-[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'product_description_translations' => 'nullable|array',
            'product_description_translations.*' => 'array|max:100',
            'product_description_translations.*.*' => 'nullable|string|max:1000',
        ], $this->pricingRules());
    }

    protected function prepareForValidation()
    {
        $pricing = $this->input('pricing', []);

        $convertedPricing = $this->prepareForPricing($pricing);

        $this->merge([
            'pricing' => $convertedPricing,
            'pinned' => $this->pinned == 'true' ? '1' : '0',
        ]);
    }

    public function store()
    {
        $validated = $this->validated();
        $product = Product::create($this->only('name', 'description', 'status', 'group_id', 'stock', 'type', 'pinned', 'sort_order'));
        Pricing::createFromArray($this->only('pricing'), $product->id);
        PricingService::forgot();
        if ($this->file('image') != null) {
            $filename = $product->id.'.'.$this->file('image')->guessExtension();
            $this->file('image')->storeAs('public'.DIRECTORY_SEPARATOR.'products', $filename);
            $product->image = 'products/'.$filename;
            $product->save();
        }
        $product->syncProductDescriptions(
            $validated['product_descriptions'] ?? [],
            $validated['product_description_translations'] ?? []
        );

        return $product;
    }
}
