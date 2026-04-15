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

namespace App\Abstracts;

use App\Contracts\Store\GatewayTypeInterface;
use App\DTO\Core\Gateway\GatewayPayInvoiceResultDTO;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Services\Store\GatewayService;
use Illuminate\Http\Request;

abstract class AbstractGatewayType implements GatewayTypeInterface
{
    protected string $name;

    protected string $uuid;

    protected string $image;

    protected string $icon;

    public function icon(): string
    {
        return $this->icon;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function checkoutForm(array $context = [])
    {
        return '';
    }

    public function configForm(array $context = [])
    {
        return '';
    }

    public function saveConfig(array $data) {}

    public function validate(): array
    {
        return [
            'secret-key' => ['required', 'string'],
            'public-key' => ['required', 'string'],
            'endpoint-secret' => ['nullable', 'string'],
        ];
    }

    public function image(): string
    {
        return \Vite::asset('resources/global/'.$this->image);
    }

    public function notification(Gateway $gateway, Request $request)
    {
        return abort(404);
    }

    abstract public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    abstract public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    public function sourceReturn(Request $request)
    {
        return '';
    }

    public function minimalAmount(): float
    {
        return collect(GatewayService::getAvailable())->where('uuid', $this->uuid)->first()->minimal_amount ?? 0;
    }

    public function addSource(Request $request)
    {
        return null;
    }

    public function removeSource(PaymentMethodSourceDTO $sourceDTO)
    {
        return null;
    }

    public function getSources(Customer $customer): array
    {
        return [];
    }

    public function sourceForm(): string
    {
        return '';
    }

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO
    {
        return new GatewayPayInvoiceResultDTO(false, 'Not implemented', $invoice, $sourceDTO);
    }

    public function getSource(Customer $customer, string $sourceId): ?PaymentMethodSourceDTO
    {
        return collect($this->getSources($customer))->where('id', $sourceId)->first();
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        return null;
    }
}
