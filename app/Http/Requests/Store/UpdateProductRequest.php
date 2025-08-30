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
use App\Services\Store\PricingService;
use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ShopProductRequest",
 *     title="Shop Product Request",
 *     description="Payload to create or update a product with associated pricing definitions",
 *     required={"name", "status", "group_id", "stock", "type"},
 *
 *     @OA\Property(property="name", type="string", example="VPS SSD 2 vCPU"),
 *     @OA\Property(property="description", type="string", example="High performance VPS with SSD storage"),
 *     @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}, example="active"),
 *     @OA\Property(property="group_id", type="integer", example=1),
 *     @OA\Property(property="stock", type="integer", example=20),
 *     @OA\Property(property="type", type="string", example="vps"),
 *     @OA\Property(property="pinned", type="boolean", example=true),
 *     @OA\Property(
 *         property="pricing",
 *         type="array",
 *         description="Array of pricing definitions for the product",
 *
 *         @OA\Items(ref="#/components/schemas/ShopPricing")
 *     ),
 *
 *     @OA\Property(
 *         property="image",
 *         type="string",
 *         format="binary",
 *         description="Product image file (jpeg, png, jpg, gif, svg)",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="remove_image",
 *         type="string",
 *         enum={"true", "false"},
 *         description="Flag to remove existing product image",
 *         example="false"
 *     )
 * )
 */
class UpdateProductRequest extends FormRequest
{
    use PricingRequestTrait;

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
        $types = app('extension')->getProductTypes()->keys()->merge(['none'])->toArray();

        return array_merge([
            'name' => 'string|max:255',
            'description' => 'string',
            'status' => 'string|in:active,hidden,unreferenced',
            'group_id' => 'integer|exists:groups,id',
            'stock' => 'integer',
            'type' => ['string', Rule::in($types)],
            'pinned' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'remove_image' => 'nullable|string|in:true,false',
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

    public function update()
    {
        $product = $this->product;
        $validated = $this->only(['name', 'description', 'status', 'group_id', 'stock', 'type', 'pinned']);
        $product->update($validated);
        $pricing = Pricing::where('related_id', $product->id)->where('related_type', 'product')->first();
        if ($pricing == null) {
            $pricing = new Pricing;
            $pricing->related_id = $product->id;
            $pricing->related_type = 'product';
        }
        $pricing->updateFromArray($this->only('pricing'));
        if ($this->file('image') != null) {
            if ($product->image != null) {
                \Storage::delete($product->image);
            }
            $filename = $product->id.'.'.$this->file('image')->getClientOriginalExtension();
            $this->file('image')->storeAs('public'.DIRECTORY_SEPARATOR.'products', $filename);
            $product->image = 'products/'.$filename;
            $product->save();
        }
        if ($this->remove_image == 'true' && $product->image != null) {
            \Storage::delete($product->image);
            $product->image = null;
            $product->save();
        }
        PricingService::forgot();

        return $product;
    }
}
