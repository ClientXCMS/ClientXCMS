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
use App\Services\Store\CurrencyService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="PricingRequest",
 *     title="Store Pricing Request",
 *     description="Schema for creating or updating a pricing related to a product or service",
 *     required={"related_id", "related_type", "currency"},
 *
 *     @OA\Property(property="related_id", type="integer", example=123, description="ID of the related product/service"),
 *     @OA\Property(property="related_type", type="string", example="product", description="Type of the related entity (e.g. product, config_option)"),
 *     @OA\Property(property="currency", type="string", example="EUR", description="Currency code (ISO 4217)"),
 *     @OA\Property(property="onetime", type="number", format="float", example=99.99, nullable=true),
 *     @OA\Property(property="monthly", type="number", format="float", example=9.99, nullable=true),
 *     @OA\Property(property="quarterly", type="number", format="float", example=27.99, nullable=true),
 *     @OA\Property(property="semiannually", type="number", format="float", example=55.99, nullable=true),
 *     @OA\Property(property="annually", type="number", format="float", example=99.99, nullable=true),
 *     @OA\Property(property="biennially", type="number", format="float", example=189.99, nullable=true),
 *     @OA\Property(property="triennially", type="number", format="float", example=279.99, nullable=true),

 *     @OA\Property(property="setup_onetime", type="number", format="float", example=9.99, nullable=true),
 *     @OA\Property(property="setup_monthly", type="number", format="float", example=2.99, nullable=true),
 *     @OA\Property(property="setup_quarterly", type="number", format="float", example=5.99, nullable=true),
 *     @OA\Property(property="setup_semiannually", type="number", format="float", example=8.99, nullable=true),
 *     @OA\Property(property="setup_annually", type="number", format="float", example=12.99, nullable=true),
 *     @OA\Property(property="setup_biennially", type="number", format="float", example=22.99, nullable=true),
 *     @OA\Property(property="setup_triennially", type="number", format="float", example=32.99, nullable=true)
 * )
 */
class PricingRequest extends FormRequest
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
        $currencies = app(CurrencyService::class)->getCurrencies()->keys()->implode(',');

        return [
            'related_id' => 'required|integer',
            'related_type' => 'required|string|in:'.implode(',', Pricing::ALLOWED_TYPES),
            'currency' => 'required|string|in:'.$currencies,
            'onetime' => 'nullable|numeric|min:0|max:999999.99',
            'monthly' => 'nullable|numeric|min:0|max:999999.99',
            'quarterly' => 'nullable|numeric|min:0|max:999999.99',
            'semiannually' => 'nullable|numeric|min:0|max:999999.99',
            'annually' => 'nullable|numeric|min:0|max:999999.99',
            'biennially' => 'nullable|numeric|min:0|max:999999.99',
            'triennially' => 'nullable|numeric|min:0|max:999999.99',
            'setup_onetime' => 'nullable|numeric|min:0|max:999999.99',
            'setup_monthly' => 'nullable|numeric|min:0|max:999999.99',
            'setup_quarterly' => 'nullable|numeric|min:0|max:999999.99',
            'setup_semiannually' => 'nullable|numeric|min:0|max:999999.99',
            'setup_annually' => 'nullable|numeric|min:0|max:999999.99',
            'setup_biennially' => 'nullable|numeric|min:0|max:999999.99',
            'setup_triennially' => 'nullable|numeric|min:0|max:999999.99',
        ];
    }
}
