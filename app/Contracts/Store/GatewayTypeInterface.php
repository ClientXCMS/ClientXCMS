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

namespace App\Contracts\Store;

use App\Abstracts\PaymentMethodSourceDTO;
use App\DTO\Core\Gateway\GatewayPayInvoiceResultDTO;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;

/**
 * Interface GatewayTypeInterface
 * This interface defines the methods that a payment gateway type must implement.
 * Abstract : `App\Abstracts\AbstractGatewayType`
 */
interface GatewayTypeInterface
{
    /**
     * Get the name of the payment gateway type.
     */
    public function name(): string;

    /**
     * Get the unique identifier for the payment gateway type.
     */
    public function uuid(): string;

    /**
     * Get the icon associated with the payment gateway type.
     */
    public function icon(): string;

    /**
     * Get the checkout form for the payment gateway type.
     *
     * @return string
     *
     * @deprecated - Not used
     */
    public function checkoutForm(array $context = []);

    /**
     * Get the configuration form for the payment gateway type in the admin panel.
     *
     * @return mixed
     */
    public function configForm(array $context = []);

    /**
     * Save the configuration data for the payment gateway type.
     *
     * @return mixed
     */
    public function saveConfig(array $data);

    /**
     * Define the validation rules for the payment gateway type.
     */
    public function validate(): array;

    /**
     * Get the image associated with the payment gateway type.
     */
    public function image(): string;

    /**
     * Get the available payment gateways.
     *
     * @return Redirect
     */
    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    /**
     * Process a payment for an invoice using the specified gateway. Generally to confirm a payment after the user has been redirected back from the payment gateway.
     *
     * @return mixed
     */
    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    /**
     * Handle the return from the payment gateway after a source attempt.
     *
     * @return mixed
     */
    public function sourceReturn(Request $request);

    /**
     * Handle the notification from the payment gateway. For example, when the payment gateway sends a webhook notification about a payment status change.
     *
     * @return mixed
     */
    public function notification(Gateway $gateway, Request $request);

    /**
     * Get the minimal amount required for a payment using this gateway.
     */
    public function minimalAmount(): float;

    /**
     * Add a payment method source for the customer.
     *
     * @return PaymentMethodSourceDTO|null|\Redirect
     */
    public function addSource(Request $request);

    /**
     * Remove a payment method source for the customer.
     *
     * @return mixed
     */
    public function removeSource(PaymentMethodSourceDTO $sourceDTO);

    /**
     * Get a specific payment method source for the customer.
     */
    public function getSource(Customer $customer, string $sourceId): ?PaymentMethodSourceDTO;

    /**
     * Get all payment method sources for the customer.
     */
    public function getSources(Customer $customer): array;

    /**
     * Get the form for adding a new payment method source.
     */
    public function sourceForm(): string;

    /**
     * Pay an invoice using a specific payment method source.
     */
    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO;

    /**
     * Get the URL for payment details of an invoice
     */
    public function getPaymentDetailsUrl(Invoice $invoice): ?string;
}
