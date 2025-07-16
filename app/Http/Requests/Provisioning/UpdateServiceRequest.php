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
namespace App\Http\Requests\Provisioning;

use App\Models\Provisioning\Service;
use App\Services\Store\RecurringService;
use App\Traits\PricingRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    use PricingRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $states = array_keys(Service::getStatuses());
        $types = app('extension')->getProductTypes()->keys()->merge(['none'])->toArray();
        $billing = app(RecurringService::class)->getRecurringTypes();

        return array_merge([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255', Rule::in($types)],
            'status' => ['required', 'string', 'max:255', Rule::in($states)],
            'billing' => ['required', 'string', 'max:255', Rule::in($billing)],
            'server_id' => ['nullable', 'integer', Rule::exists('servers', 'id')],
            'expires_at' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'suspended_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'cancelled_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'delivery_errors' => ['nullable', 'string', 'max:1000'],
            'delivery_attempts' => ['nullable', 'integer'],
            'renewals' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', Rule::in(array_keys(currencies()->toArray()))],
            'trial_ends_at' => ['nullable', 'date'],
            'max_renewals' => ['nullable', 'integer'],
            'data' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:1000'],
            'resync' => ['nullable'],
        ], $this->pricingRules());

    }

    protected function prepareForValidation()
    {
        $billing = $this->input('billing');
        $pricing = $this->input('pricing', []);
        $convertedPricing = $this->prepareForPricing($pricing);
        $this->merge([
            'product_id' => $this->product_id == 'none' ? null : (int) $this->product_id,
            'server_id' => $this->server_id == 'none' ? null : (int) $this->server_id,
            'pricing' => $convertedPricing,
        ]);
        if ($billing === 'onetime') {
            $this->merge([
                'expires_at' => null,
            ]);
        }
        if ($this->route('service')->trial_ends_at != null) {
            $this->merge(['trial_ends_at' => $this->get('expires_at')]);
        }

    }
}
