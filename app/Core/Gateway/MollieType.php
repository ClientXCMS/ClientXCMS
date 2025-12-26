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

namespace App\Core\Gateway;

use App\Abstracts\AbstractGatewayType;
use App\Abstracts\PaymentMethodSourceDTO;
use App\DTO\Core\Gateway\GatewayPayInvoiceResultDTO;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Exceptions\WrongPaymentException;
use App\Helpers\EnvEditor;
use App\Models\Account\Customer;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mollie\Laravel\Facades\Mollie;

class MollieType extends AbstractGatewayType
{
    const UUID = 'mollie';

    protected string $name = 'Mollie';

    protected string $uuid = self::UUID;

    protected string $image = 'mollie-icon.png';

    protected string $icon = 'bi bi-credit-card';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $this->initMollie();
        try {
            $payment = Mollie::api()->payments->create([
                'amount' => [
                    'currency' => strtoupper($invoice->currency),
                    'value' => number_format($invoice->total, 2, '.', ''),
                ],
                'description' => __('global.invoice') . ' #' . $invoice->id,
                'redirectUrl' => $dto->returnUri,
                'webhookUrl' => $dto->notificationUri,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer->id,
                ],
            ]);

            $invoice->update(['external_id' => $payment->id]);

            return redirect($payment->getCheckoutUrl(), 303);
        } catch (\Exception $e) {
            throw new WrongPaymentException('Mollie payment creation failed: ' . $e->getMessage());
        }
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        return redirect()->route('front.invoices.show', $invoice);
    }

    public function notification(Gateway $gateway, Request $request)
    {
        $this->initMollie();

        $paymentId = $request->input('id');
        if (!$paymentId) {
            return response()->json(['error' => 'Missing payment ID'], 400);
        }

        try {
            $payment = Mollie::api()->payments->get($paymentId);
        } catch (\Exception $e) {
            logger()->error('Mollie webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $invoiceId = $payment->metadata->invoice_id ?? null;
        if (!$invoiceId) {
            return response()->json(['error' => 'Missing invoice ID in metadata'], 400);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice->update(['external_id' => $payment->id]);

        if ($payment->isPaid()) {
            $invoice->complete();
            return response()->json(['success' => true, 'message' => 'Payment completed']);
        }

        if ($payment->isFailed() || $payment->isExpired() || $payment->isCanceled()) {
            return response()->json(['success' => false, 'message' => 'Payment ' . $payment->status]);
        }

        return response()->json(['success' => true, 'message' => 'Payment status: ' . $payment->status]);
    }

    public function validate(): array
    {
        return [
            'api_key' => 'required|string',
            'test_mode' => 'required|in:live,test',
        ];
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'MOLLIE_KEY' => $data['api_key'],
            'MOLLIE_TEST_MODE' => $data['test_mode'] === 'test' ? 'true' : 'false',
        ]);
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.mollie', $context);
    }

    private function initMollie(): void
    {
        $apiKey = env('MOLLIE_KEY');
        if (!$apiKey) {
            throw new WrongPaymentException('Mollie API key not configured');
        }
    }

    public function sourceForm(): string
    {
        return view('admin.settings.store.gateways.mollie-source')->render();
    }

    public function addSource(Request $request)
    {
        $this->initMollie();

        /** @var Customer $customer */
        $customer = $request->user('web');
        if (!$customer) {
            return null;
        }

        try {
            // Get or create Mollie customer
            $mollieCustomerId = $customer->getMetadata('mollie_customer_id');

            if (!$mollieCustomerId) {
                $mollieCustomer = Mollie::api()->customers->create([
                    'name' => $customer->firstname . ' ' . $customer->lastname,
                    'email' => $customer->email,
                    'metadata' => ['customer_id' => $customer->id],
                ]);
                $mollieCustomerId = $mollieCustomer->id;
                $customer->attachMetadata('mollie_customer_id', $mollieCustomerId);
            }

            // Create first payment to set up mandate
            $payment = Mollie::api()->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => '0.01',
                ],
                'customerId' => $mollieCustomerId,
                'sequenceType' => 'first',
                'description' => 'Setup payment method',
                'redirectUrl' => route('gateways.source.return', ['gateway' => self::UUID]),
                'webhookUrl' => route('gateways.notification', ['gateway' => self::UUID]),
            ]);

            return redirect($payment->getCheckoutUrl(), 303);
        } catch (\Exception $e) {
            logger()->error('Mollie add source error: ' . $e->getMessage());
            return null;
        }
    }

    public function sourceReturn(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('web')->user();
        if (!$customer) {
            return redirect()->route('front.payment-methods.index')->with('error', __('client.payment-methods.errors.not_found'));
        }

        return redirect()->route('front.payment-methods.index')->with('success', __('client.payment-methods.success'));
    }

    public function removeSource(PaymentMethodSourceDTO $sourceDTO)
    {
        $this->initMollie();

        try {
            /** @var Customer $customer */
            $customer = Auth::guard('web')->user();
            $mollieCustomerId = $customer->getMetadata('mollie_customer_id');

            if ($mollieCustomerId) {
                Mollie::api()->mandates->revokeForId($mollieCustomerId, $sourceDTO->id);
            }
        } catch (\Exception $e) {
            logger()->error('Mollie remove source error: ' . $e->getMessage());
        }
    }

    public function getSources(Customer $customer): array
    {
        $this->initMollie();

        $mollieCustomerId = $customer->getMetadata('mollie_customer_id');
        if (!$mollieCustomerId) {
            return [];
        }

        try {
            $mandates = Mollie::api()->mandates->listForId($mollieCustomerId);
        } catch (\Exception $e) {
            logger()->error('Mollie get sources error: ' . $e->getMessage());
            return [];
        }

        $sources = [];
        foreach ($mandates as $mandate) {
            if ($mandate->status === 'valid') {
                $sources[] = new PaymentMethodSourceDTO(
                    $mandate->id,
                    $mandate->method ?? 'SEPA',
                    $mandate->details->consumerAccount ?? '****',
                    '-',
                    '-',
                    $customer->id,
                    self::UUID,
                    $mandate->details->consumerName ?? null
                );
            }
        }

        return $sources;
    }

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO
    {
        $this->initMollie();

        $mollieCustomerId = $invoice->customer->getMetadata('mollie_customer_id');
        if (!$mollieCustomerId) {
            return new GatewayPayInvoiceResultDTO(false, 'Customer has no Mollie ID', $invoice, $sourceDTO);
        }

        try {
            $payment = Mollie::api()->payments->create([
                'amount' => [
                    'currency' => strtoupper($invoice->currency),
                    'value' => number_format($invoice->total, 2, '.', ''),
                ],
                'customerId' => $mollieCustomerId,
                'sequenceType' => 'recurring',
                'mandateId' => $sourceDTO->id,
                'description' => __('global.invoice') . ' #' . $invoice->id,
                'webhookUrl' => route('gateways.notification', ['gateway' => self::UUID]),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer->id,
                ],
            ]);

            $invoice->update(['external_id' => $payment->id]);
            $invoice->attachMetadata('used_payment_method', $sourceDTO->id);

            if ($payment->isPaid()) {
                $invoice->complete();
                return new GatewayPayInvoiceResultDTO(true, 'Payment completed', $invoice, $sourceDTO);
            }

            return new GatewayPayInvoiceResultDTO(true, 'Payment pending: ' . $payment->status, $invoice, $sourceDTO);
        } catch (\Exception $e) {
            logger()->error('Mollie payInvoice error: ' . $e->getMessage());
            return new GatewayPayInvoiceResultDTO(false, $e->getMessage(), $invoice, $sourceDTO);
        }
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            $testMode = env('MOLLIE_TEST_MODE', 'true') === 'true';
            $baseUrl = $testMode
                ? 'https://my.mollie.com/dashboard/org_1/payments/'
                : 'https://my.mollie.com/dashboard/payments/';
            return $baseUrl . $invoice->external_id;
        }
        return null;
    }
}
