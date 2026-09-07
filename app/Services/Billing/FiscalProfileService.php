<?php

namespace App\Services\Billing;

use App\Helpers\Countries;
use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FiscalProfileService
{
    public const ROUTING_EINVOICING = 'einvoicing';

    public const ROUTING_EREPORTING = 'ereporting';

    public const ROUTING_MANUAL_REVIEW = 'manual_review';

    public function update(Customer $customer, array $input): Customer
    {
        $legalName = array_key_exists('legal_name', $input)
            ? $input['legal_name']
            : ($input['company_name'] ?? $customer->legal_name ?? $customer->company_name);
        $customerType = $input['customer_type'] ?? (
            filled($input['company_name'] ?? null)
                ? Customer::TYPE_BUSINESS
                : ($customer->customer_type ?? Customer::TYPE_INDIVIDUAL)
        );
        $input = array_merge([
            'customer_type' => $customerType,
            'tax_subject_status' => $customer->tax_subject_status ?? Customer::TAX_STATUS_UNKNOWN,
            'legal_name' => $legalName,
            'siren' => $customer->siren,
            'siret' => $customer->siret,
            'vat_number' => $customer->vat_number,
            'tax_registration_number' => $customer->tax_registration_number,
            'rna_number' => $customer->rna_number,
            'address' => $customer->address,
            'address2' => $customer->address2,
            'zipcode' => $customer->zipcode,
            'city' => $customer->city,
            'region' => $customer->region,
            'country' => $customer->country,
            'billing_details' => $customer->billing_details,
        ], $input, ['legal_name' => $legalName, 'customer_type' => $customerType]);
        $country = strtoupper((string) $input['country']);

        $data = Validator::make($input, [
            'customer_type' => ['required', Rule::in([Customer::TYPE_INDIVIDUAL, Customer::TYPE_BUSINESS, Customer::TYPE_ASSOCIATION])],
            'tax_subject_status' => ['required', Rule::in([
                Customer::TAX_STATUS_UNKNOWN,
                Customer::TAX_STATUS_NON_TAXABLE,
                Customer::TAX_STATUS_TAXABLE_NOT_VAT_LIABLE,
                Customer::TAX_STATUS_VAT_LIABLE,
            ])],
            'legal_name' => ['nullable', Rule::requiredIf(fn () => in_array($input['customer_type'] ?? null, [Customer::TYPE_BUSINESS, Customer::TYPE_ASSOCIATION], true)), 'string', 'max:255', 'regex:/^[^<>]*$/'],
            'siren' => ['nullable', Rule::requiredIf(fn () => $this->requiresFrenchSiren($input, $country)), 'regex:/^\d{9}$/'],
            'siret' => ['nullable', 'regex:/^\d{14}$/'],
            'vat_number' => ['nullable', Rule::requiredIf(fn () => ($input['customer_type'] ?? null) === Customer::TYPE_ASSOCIATION && ($input['tax_subject_status'] ?? null) === Customer::TAX_STATUS_VAT_LIABLE), 'string', 'max:32', 'regex:/^[A-Za-z0-9. -]+$/'],
            'tax_registration_number' => ['nullable', Rule::requiredIf(fn () => $this->requiresForeignTaxIdentifier($input, $country)), 'string', 'max:64', 'regex:/^[A-Za-z0-9. _-]+$/'],
            'rna_number' => ['nullable', 'regex:/^W\d{9}$/i'],
            'address' => ['required', 'string', 'max:250', 'regex:/^[^<>]*$/'],
            'address2' => ['nullable', 'string', 'max:250', 'regex:/^[^<>]*$/'],
            'zipcode' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:250', 'regex:/^[^<>]*$/'],
            'region' => ['required', 'string', 'max:250', 'regex:/^[^<>]*$/'],
            'country' => ['required', 'string', Rule::in(array_keys(Countries::names()))],
            'billing_details' => ['nullable', 'string', 'max:255', 'regex:/^[^<>]*$/'],
        ])->validate();

        if ($data['customer_type'] === Customer::TYPE_INDIVIDUAL) {
            $data = array_merge($data, [
                'tax_subject_status' => Customer::TAX_STATUS_UNKNOWN,
                'legal_name' => null,
                'siren' => null,
                'siret' => null,
                'vat_number' => null,
                'tax_registration_number' => null,
                'rna_number' => null,
            ]);
        } elseif ($data['customer_type'] === Customer::TYPE_BUSINESS) {
            $data['tax_subject_status'] = Customer::TAX_STATUS_UNKNOWN;
            $data['rna_number'] = null;
        } elseif ($data['tax_subject_status'] === Customer::TAX_STATUS_NON_TAXABLE) {
            $data['vat_number'] = null;
        }
        if ($data['customer_type'] !== Customer::TYPE_INDIVIDUAL && $country !== 'FR') {
            $data['siren'] = null;
            $data['siret'] = null;
        }

        DB::transaction(function () use ($customer, $data) {
            $customer->fill($data + ['company_name' => $data['legal_name'] ?? null]);
            $customer->fiscal_profile_completed = $this->isComplete($customer);
            $customer->save();
        });

        return $customer->refresh();
    }

    public function isComplete(Customer $customer): bool
    {
        if ($customer->customer_type === Customer::TYPE_INDIVIDUAL) {
            return true;
        }

        if (blank($customer->legal_name)) {
            return false;
        }

        if ($customer->customer_type === Customer::TYPE_ASSOCIATION) {
            if (collect(['address', 'zipcode', 'city', 'country'])->contains(fn (string $field) => blank($customer->{$field}))) {
                return false;
            }
            if (! in_array($customer->tax_subject_status, [
                Customer::TAX_STATUS_NON_TAXABLE,
                Customer::TAX_STATUS_TAXABLE_NOT_VAT_LIABLE,
                Customer::TAX_STATUS_VAT_LIABLE,
            ], true)) {
                return false;
            }
            if ($customer->tax_subject_status === Customer::TAX_STATUS_VAT_LIABLE && blank($customer->vat_number)) {
                return false;
            }
            if ($customer->tax_subject_status === Customer::TAX_STATUS_NON_TAXABLE) {
                return true;
            }
        }

        return strtoupper($customer->country) === 'FR'
            ? preg_match('/^\d{9}$/', (string) $customer->siren) === 1
            : filled($customer->tax_registration_number) || filled($customer->vat_number);
    }

    public function electronicRouting(Customer $customer): string
    {
        if ($customer->customer_type === Customer::TYPE_INDIVIDUAL) {
            return self::ROUTING_EREPORTING;
        }
        if ($customer->customer_type === Customer::TYPE_ASSOCIATION) {
            if ($customer->tax_subject_status === Customer::TAX_STATUS_UNKNOWN) {
                return self::ROUTING_MANUAL_REVIEW;
            }
            if ($customer->tax_subject_status === Customer::TAX_STATUS_NON_TAXABLE) {
                return self::ROUTING_EREPORTING;
            }
        }

        return strtoupper((string) $customer->country) === 'FR'
            ? self::ROUTING_EINVOICING
            : self::ROUTING_EREPORTING;
    }

    public function isReadyForIssuance(Customer $customer): bool
    {
        if ($this->isComplete($customer)) {
            return true;
        }

        return $this->electronicRouting($customer) === self::ROUTING_MANUAL_REVIEW
            && $customer->customer_type === Customer::TYPE_ASSOCIATION
            && filled($customer->legal_name)
            && ! collect(['address', 'zipcode', 'city', 'country'])->contains(
                fn (string $field) => blank($customer->{$field})
            );
    }

    public function snapshot(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        $address = $invoice->billing_address ?: $customer->generateBillingAddress();

        $snapshot = [
            'seller' => [
                'legal_name' => setting('billing_legal_name', setting('app.name')),
                'siren' => setting('billing_siren'),
                'siret' => setting('billing_siret'),
                'vat_number' => setting('billing_vat_number'),
                'address' => setting('app_address'),
            ],
            'buyer' => [
                'type' => $customer->customer_type,
                'legal_name' => $customer->legal_name ?: $customer->company_name,
                'siren' => $customer->siren,
                'siret' => $customer->siret,
                'vat_number' => $customer->vat_number,
                'tax_registration_number' => $customer->tax_registration_number,
                'rna_number' => $customer->rna_number,
                'tax_subject_status' => $customer->tax_subject_status,
                'additional_details' => $address['billing_details'] ?? $customer->billing_details,
                'address' => Arr::only($address, ['address', 'address2', 'zipcode', 'city', 'region', 'country']),
                'email' => $address['email'] ?? $customer->email,
            ],
            'delivery' => null,
            'tax' => [
                'country' => $customer->country,
                'operation_category' => setting('billing_operation_category', 'services'),
                'vat_on_debits' => filter_var(setting('billing_vat_on_debits', false), FILTER_VALIDATE_BOOL),
                'electronic_routing' => $this->electronicRouting($customer),
            ],
            'payment' => [
                'method' => $invoice->paymethod,
                'currency' => $invoice->currency,
                'due_date' => optional($invoice->due_date)->toDateString(),
            ],
        ];

        $snapshot['evidence'] = $this->evidence($customer, true);

        return $snapshot;
    }

    public function evidence(Customer $customer, bool $refresh = false): array
    {
        $evidence = [];
        foreach (app()->tagged('billing.fiscal-evidence') as $provider) {
            $item = $refresh ? $provider->evidenceFor($customer) : $provider->latestEvidenceFor($customer);
            if ($item) {
                $evidence[] = $item;
            }
        }

        return $evidence;
    }

    public function status(Customer $customer): string
    {
        if (! $this->isComplete($customer) || ! $customer->fiscal_profile_completed) {
            return 'incomplete';
        }
        $pending = ['pending', 'unavailable', 'member_state_unavailable', 'timeout', 'rate_limited'];

        return collect($this->evidence($customer))->contains(fn (array $item) => in_array($item['status'] ?? null, $pending, true))
            ? 'pending'
            : 'complete';
    }

    private function requiresFrenchSiren(array $input, string $country): bool
    {
        if ($country !== 'FR') {
            return false;
        }
        if (($input['customer_type'] ?? null) === Customer::TYPE_BUSINESS) {
            return true;
        }

        return ($input['customer_type'] ?? null) === Customer::TYPE_ASSOCIATION
            && in_array($input['tax_subject_status'] ?? null, [Customer::TAX_STATUS_TAXABLE_NOT_VAT_LIABLE, Customer::TAX_STATUS_VAT_LIABLE], true);
    }

    private function requiresForeignTaxIdentifier(array $input, string $country): bool
    {
        if ($country === 'FR' || filled($input['vat_number'] ?? null)) {
            return false;
        }

        return ($input['customer_type'] ?? null) === Customer::TYPE_BUSINESS
            || (($input['customer_type'] ?? null) === Customer::TYPE_ASSOCIATION
                && in_array($input['tax_subject_status'] ?? null, [Customer::TAX_STATUS_TAXABLE_NOT_VAT_LIABLE, Customer::TAX_STATUS_VAT_LIABLE], true));
    }
}
