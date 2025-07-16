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
namespace App\Contracts\Store;

use App\Abstracts\PaymentMethodSourceDTO;
use App\DTO\Core\Gateway\GatewayPayInvoiceResultDTO;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use Illuminate\Http\Request;

interface GatewayTypeInterface
{
    public function name(): string;

    public function uuid(): string;

    public function icon(): string;

    public function checkoutForm(array $context = []);

    public function configForm(array $context = []);

    public function saveConfig(array $data);

    public function validate(): array;

    public function image(): string;

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    public function createSubscription(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto);

    public function cancelSubscription(Subscription $subscription): ?Subscription;

    public function notification(Gateway $gateway, Request $request);

    public function minimalAmount(): float;

    public function addSource(Request $request): ?PaymentMethodSourceDTO;

    public function removeSource(PaymentMethodSourceDTO $sourceDTO);

    public function getSource(Customer $customer, string $sourceId): ?PaymentMethodSourceDTO;

    public function getSources(Customer $customer): array;

    public function sourceForm(): string;

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO;

    public function getPaymentDetailsUrl(Invoice $invoice): ?string;
}
