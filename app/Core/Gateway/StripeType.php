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
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeType extends AbstractGatewayType
{
    const UUID = 'stripe';

    const VERSION = '2023-10-16';

    protected string $name = 'Stripe';

    protected string $uuid = self::UUID;

    private ?StripeClient $stripe = null;

    protected string $image = 'stripe-icon.png';

    protected string $icon = 'bi bi-stripe';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $this->initStripe();
        $rate = $invoice->tax != 0 ? [$this->getStripeRate($invoice->customer)] : null;
        $customer = $this->getCustomerStripe($invoice->customer);
        try {
            $session = \Stripe\Checkout\Session::create([
                'customer' => $customer->id,
                'payment_method_types' => $this->getPaymentMethodTypes(),
                'line_items' => [[
                    'quantity' => 1,
                    'tax_rates' => $rate,
                    'price_data' => [
                        'currency' => $invoice->currency,
                        'unit_amount' => (int) ($invoice->total * 100),
                        'product_data' => [
                            'name' => __('global.invoice').' #'.$invoice->id,
                        ],
                    ],
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $invoice->customer->id,
                ],
                'success_url' => $dto->returnUri,
                'cancel_url' => $dto->cancelUri,
            ]);
        } catch (InvalidRequestException $e) {
            throw new WrongPaymentException('Payment method type is invalid. : '.$e->getMessage());
        }

        return redirect($session->url);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        return redirect()->route('front.invoices.show', $invoice);
    }

    public function notification(Gateway $gateway, Request $request)
    {
        $signature = $request->header('Stripe-Signature');
        if (env('STRIPE_WEBHOOK_SECRET') == null) {
            return response()->json(['error' => 'Stripe webhook secret not found'], 400);
        }
        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                env('STRIPE_WEBHOOK_SECRET')
            );
            $this->initStripe();
            if ($event->type == 'checkout.session.completed') {

                $object = $event->data->object;
                $id = $object->metadata->invoice_id ?? 0;
                $invoice = Invoice::find($id);
                if ($invoice == null) {
                    return response()->json(['error' => 'Invoice not found'], 400);
                }
                $intent = \Stripe\PaymentIntent::retrieve($object->payment_intent);

                $invoice->update(['external_id' => $intent->id, 'fees' => $intent->application_fee_amount / 100]);
                $invoice->complete();

                return response()->json(['success' => 'Invoice paid', 'fees' => $intent->application_fee_amount / 100]);
            }
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'STRIPE_PRIVATE_KEY' => $data['private_key'],
            'STRIPE_PUBLIC_KEY' => $data['public_key'],
            'STRIPE_WEBHOOK_SECRET' => $data['webhook_secret'],
            'STRIPE_PAYMENT_TYPES' => implode(',', $data['payment_types']),
        ]);
    }

    public function validate(): array
    {
        return [
            'private_key' => 'required|string',
            'public_key' => 'required|string',
            'webhook_secret' => 'required|string',
            'payment_types' => 'required|array',
        ];
    }

    public function configForm(array $context = [])
    {
        $context['options'] = ['acss_debit', 'affirm', 'paypal', 'afterpay_clearpay', 'alipay', 'au_becs_debit', 'bacs_debit', 'bancontact', 'blik', 'boleto', 'card', 'customer_balance', 'eps', 'fpx', 'giropay', 'grabpay', 'ideal', 'klarna', 'konbini', 'link', 'oxxo', 'p24', 'paynow', 'pix', 'promptpay', 'sepa_debit', 'sofort', 'us_bank_account', 'wechat_pay'];
        $context['options'] = collect($context['options'])->mapWithKeys(fn ($option) => [$option => $option])->toArray();

        return view('admin.settings.store.gateways.stripe', $context);
    }

    /**
     * @throws WrongPaymentException
     */
    private function initStripe(): StripeClient
    {
        if ($this->stripe == null) {
            $privateKey = env('STRIPE_PRIVATE_KEY');
            $publicKey = env('STRIPE_PUBLIC_KEY');
            if ($privateKey == null || $publicKey == null) {
                throw new WrongPaymentException('Stripe keys not found');
            }
            $stripe = new StripeClient($privateKey);
            Stripe::setApiKey($privateKey);
            Stripe::setApiVersion(self::VERSION);
            $this->stripe = $stripe;
        }

        return $this->stripe;
    }

    private function getCustomerStripe(Customer $customer, bool $create = true): \Stripe\Customer
    {
        $customers = $this->stripe->customers->search([
            'query' => 'email:'.'"'.$customer->email.'"',
        ]);
        if (empty($customers->data) && $create) {
            return $this->stripe->customers->create([
                'email' => $customer->email,
                'name' => $customer->firstname.' '.$customer->lastname,
                'phone' => $customer->phone,
                'address' => [
                    'line1' => $customer->address,
                    'city' => $customer->city,
                    'postal_code' => $customer->zipcode,
                    'country' => $customer->country,
                ],
                'metadata' => [
                    'id' => $customer->id,
                ],
            ]);
        } else {
            if (! empty($customers->data)) {
                return $customers->data[0];
            }
            throw new WrongPaymentException(sprintf('Customer not found for %s', $customer->email));
        }
    }

    private function getPaymentMethodTypes(): array
    {
        return explode(',', env('STRIPE_PAYMENT_TYPES', 'card'));
    }

    private function getStripeRate(Customer $customer): ?string
    {
        try {
            $this->initStripe();
            $rates = $this->stripe->taxRates->all();
        } catch (WrongPaymentException|ApiErrorException $e) {
            logger()->error($e->getMessage());

            return null;
        }
        foreach ($rates as $rate) {
            if ($rate->country == $customer->country && $rate->active && $rate->inclusive == is_tax_included()) {
                return $rate->id;
            }
        }

        return $this->stripe->taxRates->create([
            'display_name' => 'TVA',
            'description' => 'VAT '.$customer->country.' '.(is_tax_excluded() ? 'Excluded' : 'Included'),
            'country' => $customer->country,
            'percentage' => tax_percent(),
            'inclusive' => ! is_tax_excluded(),
        ])->id;
    }

    public function sourceForm(): string
    {
        return view('admin.settings.store.gateways.stripe-source', ['public' => env('STRIPE_PUBLIC_KEY')])->render();
    }

    public function addSource(Request $request): ?PaymentMethodSourceDTO
    {
        $valid = $request->validate([
            'stripe_token' => 'required|string',
        ]);
        try {
            $this->initStripe();
            $user = $request->user('web');
            $customer = $this->getCustomerStripe($user);
        } catch (WrongPaymentException $e) {
            logger()->error($e->getMessage());

            return null;
        }
        try {
            $paymentMethod = $this->stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'token' => $valid['stripe_token'],
                ],
                'metadata' => [
                    'user_id' => $request->user()->id,
                ],
            ]);
        } catch (ApiErrorException $e) {
            return null;
        }
        $this->stripe->paymentMethods->attach(
            $paymentMethod->id,
            ['customer' => $customer->id]
        );
        if ($user->getDefaultPaymentMethod() == null) {
            $user->setDefaultPaymentMethod($paymentMethod->id);
        }

        return new PaymentMethodSourceDTO($paymentMethod->id, $paymentMethod->card->brand, $paymentMethod->card->last4, $paymentMethod->card->exp_month, $paymentMethod->card->exp_year, $user->id, 'stripe');
    }

    public function removeSource(PaymentMethodSourceDTO $sourceDTO)
    {
        try {
            $this->initStripe();
            $this->stripe->paymentMethods->detach($sourceDTO->id);
        } catch (\Exception $e) {
            logger()->error($e->getMessage());
        }
    }

    public function getSources(Customer $customer): array
    {
        try {
            $this->initStripe();
            $stripecustomer = $this->getCustomerStripe($customer, false);
            $sources = $this->stripe->paymentMethods->all([
                'customer' => $stripecustomer->id,
                'type' => 'card',
            ]);
        } catch (\Exception $e) {
            return [];
        }
        $cards = [];
        foreach ($sources as $source) {
            $cards[] = new PaymentMethodSourceDTO($source->id, $source->card->brand, $source->card->last4, $source->card->exp_month, $source->card->exp_year, $customer->id, 'stripe');
        }

        return $cards;
    }

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO
    {
        try {
            $this->initStripe();
            $customerId = $this->getCustomerStripe($invoice->customer)->id;
        } catch (WrongPaymentException $e) {
            return new GatewayPayInvoiceResultDTO(false, $e->getMessage(), $invoice, $sourceDTO);
        }
        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($invoice->total * 100),
                'currency' => $invoice->currency,
                'customer' => $customerId,
                'description' => __('global.invoice').' #'.$invoice->id,
                'payment_method' => $sourceDTO->id,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'confirm' => true,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'user_id' => $invoice->customer->id,
                ],
            ]);
        } catch (\Exception $e) {
            return new GatewayPayInvoiceResultDTO(false, $e->getMessage(), $invoice, $sourceDTO);
        }
        if ($intent->status == 'succeeded') {
            $invoice->update(['external_id' => $intent->id, 'fees' => $intent->application_fee_amount / 100]);
            $invoice->attachMetadata('used_payment_method', $sourceDTO->id);
            $invoice->complete();
        }

        return new GatewayPayInvoiceResultDTO($intent->status == 'succeeded', $intent->status, $invoice, $sourceDTO);
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            return 'https://dashboard.stripe.com/payments/'.$invoice->external_id;
        }

        return null;
    }
}
