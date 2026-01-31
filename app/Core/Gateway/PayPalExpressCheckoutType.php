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
use Illuminate\Support\Str;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpClient;
use PayPalHttp\HttpRequest;

class PayPalExpressCheckoutType extends AbstractGatewayType
{
    const UUID = 'paypal_express_checkout';

    protected string $name = 'PayPal Express';

    protected string $uuid = self::UUID;

    protected string $image = 'paypal-icon.png';

    protected string $icon = 'bi bi-paypal';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $order = new OrdersCreateRequest;
        $order->prefer('return=representation');
        $order->body = [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => $dto->returnUri,
                'cancel_url' => $dto->cancelUri,
                'brand_name' => setting('app_name'),
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
            ],
            'purchase_units' => [
                [
                    'reference_id' => $invoice->id,
                    'description' => 'Invoice #'.$invoice->id,
                    'custom_id' => $invoice->id,
                    'amount' => [
                        'currency_code' => $invoice->currency,
                        'value' => (string) number_format($invoice->total, 2),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $invoice->currency,
                                'value' => (string) number_format($invoice->subtotal, 2),
                            ],
                            'tax_total' => [
                                'currency_code' => $invoice->currency,
                                'value' => (string) number_format($invoice->tax, 2),
                            ],
                        ],
                    ],
                    'items' => [[
                        'name' => __('global.invoice').' #'.$invoice->id,
                        'quantity' => '1',
                        'category' => 'DIGITAL_GOODS',
                        'unit_amount' => [
                            'currency_code' => $invoice->currency,
                            'value' => (string) number_format($invoice->subtotal, 2),
                        ],
                        'description' => __('global.invoice').' #'.$invoice->id,
                        'sku' => 'invoice-'.$invoice->id,
                    ]],
                ],
            ],
        ];
        try {
            $response = $this->executeClient($order);
        } catch (\Exception $e) {
            throw new WrongPaymentException($e->getMessage());
        }
        $invoice->update(['external_id' => $response->result->id]);
        $result = $response->result;
        $approveLink = collect($result->links)->first(function ($link) {
            return $link->rel === 'approve';
        });

        return redirect($approveLink->href);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {

        $token = $request->query('token');
        if ($token != $invoice->external_id) {
            throw new WrongPaymentException('Wrong PayPal token');
        }
        try {
            $request = new OrdersCaptureRequest($token);
            $request->prefer('return=representation');
            $responsePayPal = $this->executeClient($request);
            $result = $responsePayPal->result;
            if ($result->status === 'COMPLETED') {
                $invoice->update(['external_id' => $result->purchase_units[0]->payments->captures[0]->id, 'fees' => $result->purchase_units[0]->payments->captures[0]->seller_receivable_breakdown->paypal_fee->value]);
                $invoice->complete();

                return redirect()->route('front.invoices.show', $invoice);
            }
        } catch (\Exception $e) {
            throw new WrongPaymentException($e->getMessage());
        }
    }

    public function validate(): array
    {
        return [
            'client_id' => 'required',
            'client_secret' => 'required',
            'sandbox' => 'required',
        ];
    }

    public function saveConfig(array $data)
    {
        try {
            $clientId = $data['client_id'];
            $clientSecret = $data['client_secret'];
            $live = ! ($data['sandbox'] == 'sandbox');
            if ($live) {
                $client = new HttpClient(new SandboxEnvironment($clientId, $clientSecret));
            } else {
                $client = new HttpClient(new ProductionEnvironment($clientId, $clientSecret));
            }
            $response = $client->execute(new OrdersCreateRequest);
        } catch (\Exception $e) {
            // throw new PaymentConfigException($e->getMessage());
        }
        EnvEditor::updateEnv([
            'PAYPAL_CLIENT_ID' => $data['client_id'],
            'PAYPAL_CLIENT_SECRET' => $data['client_secret'],
            'PAYPAL_SANDBOX' => ! $live ? 'true' : 'false',
        ]);
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.paypalexpresscheckout', $context);
    }

    private function getClient()
    {
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_CLIENT_SECRET');
        $sandbox = env('PAYPAL_SANDBOX');

        $mode = $sandbox == 'true' ? 'sandbox' : 'live';

        if ($clientId === null || $clientSecret === null) {
            throw new WrongPaymentException('PayPal client id or secret is not set.');
        }
        if ($mode == 'sandbox') {
            return new HttpClient(new SandboxEnvironment($clientId, $clientSecret));
        }

        return new HttpClient(new ProductionEnvironment($clientId, $clientSecret));
    }

    public function sourceForm(): string
    {
        return view('admin.settings.store.gateways.paypal-source')->render();
    }

    public function sourceReturn(Request $request)
    {
        $token = $request->query('approval_token_id');
        if (! $token) {
            return redirect()->route('front.payment-methods.index')->with('error', __('client.payment-methods.errors.not_found'));
        }
        $req = new HttpRequest('/v3/vault/payment-tokens/', 'POST');
        $req->body = json_encode([
            'approval_token_id' => $token,
            'customer' => ['id' => 'CLIENTXCMS-'.auth()->id()],
            'payment_source' => [
                'token' => [
                    'id' => $token,
                    'type' => 'SETUP_TOKEN',
                ],
            ],
        ]);
        try {
            $resp = $this->executeClient($req);
            if ($resp->statusCode === 200) {
                $source = $resp->result->customer->id;
                /** @var Customer $customer */
                $customer = auth()->user();
                if (! $customer) {
                    logger()->error('Cannot find customer for PayPal source return');

                    return redirect()->route('front.payment-methods.index')->with('error', __('store.checkout.wrong_payment'));
                }
                $customer->attachMetadata('paypal_customer_id', $source);
                if ($customer->getDefaultPaymentMethod() == null) {
                    $customer->setDefaultPaymentMethod($resp->result->payment_tokens[0]->id);
                }

                return redirect()->route('front.payment-methods.index')->with('success', __('client.payment-methods.success'));
            }
        } catch (\Exception $e) {
            logger()->error('PayPal source return error: '.$e->getMessage());

            return redirect()->route('front.payment-methods.index')->with('error', __(''));
        }
    }

    public function removeSource(PaymentMethodSourceDTO $sourceDTO)
    {
        $req = new HttpRequest('/v3/vault/payment-tokens/'.$sourceDTO->id, 'DELETE');
        try {
            $resp = $this->executeClient($req);
            if ($resp->statusCode === 204) {
                /** @var Customer $customer */
                $customer = auth()->user();
                if (! $customer) {
                    logger()->error('Cannot find customer for PayPal source removal');

                    return redirect()->route('front.payment-methods.index')->with('error', __('store.checkout.wrong_payment'));
                }
            }
        } catch (\Exception $e) {
            logger()->error('PayPal source removal error: '.$e->getMessage());
        }
    }

    public function addSource(Request $request)
    {
        $req = new HttpRequest('/v3/vault/setup-tokens', 'POST');
        $req->body = json_encode([
            'payment_source' => [
                'paypal' => [
                    'description' => 'Test Content',
                    'usage_pattern' => 'IMMEDIATE',
                    'usage_type' => 'MERCHANT',
                    'customer_type' => 'CONSUMER',
                    'experience_context' => [
                        'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => setting('app_name'),
                        'locale' => 'en-US',
                        'return_url' => route('gateways.source.return', ['gateway' => 'paypal_express_checkout']),
                        'cancel_url' => route('front.payment-methods.index'),
                    ],
                ],
            ],
            'customer' => ['id' => 'CLIENTXCMS-'.auth()->id()],
        ]);
        try {
            $resp = $this->executeClient($req);
            $approveLink = collect($resp->result->links)->first(fn ($link) => $link->rel === 'approve');

            return redirect($approveLink->href);
        } catch (\Exception $e) {
            throw new WrongPaymentException($e->getMessage());
        }
    }

    public function payInvoice(Invoice $invoice, PaymentMethodSourceDTO $sourceDTO): GatewayPayInvoiceResultDTO
    {
        $vaultId = $invoice->customer->getMetadata('paypal_vault_id');

        if (! $vaultId) {
            throw new WrongPaymentException('The customer does not have a PayPal vault_id.');
        }

        $orderReq = new OrdersCreateRequest;
        $orderReq->prefer('return=representation');

        $orderReq->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $invoice->id,
                    'amount' => [
                        'currency_code' => $invoice->currency,

                        'value' => number_format($invoice->total, 2, '.', ''),
                    ],
                    'description' => 'Payment for Invoice #'.$invoice->id,
                ],
            ],
            'payment_source' => [
                'token' => [
                    'id' => $sourceDTO->id,
                    'type' => 'PAYMENT_METHOD_TOKEN',
                ],
            ],
        ];
        try {
            $orderRes = $this->executeClient($orderReq);
        } catch (\PayPalHttp\HttpException $e) {
            logger()->error($e->getMessage());
            throw new WrongPaymentException('PayPal order creation failed: '.$e->getMessage());
        } catch (\Exception $e) {
            logger()->error($e->getMessage());
            throw new WrongPaymentException('An unexpected error occurred during PayPal order creation: '.$e->getMessage());
        }
        $orderId = $orderRes->result->id;
        $status = $orderRes->result->status;
        if ($status === 'APPROVED') {
            $captureReq = new OrdersCaptureRequest($orderId);
            $captureReq->prefer('return=representation');
            $captureReq->payPalRequestId((string) Str::uuid());
            try {
                $captureRes = $this->executeClient($captureReq);
            } catch (\PayPalHttp\HttpException $e) {
                logger()->error($e->getMessage());
                throw new WrongPaymentException('PayPal capture failed: '.$e->getMessage());
            } catch (\Exception $e) {
                logger()->error($e->getMessage());
                throw new WrongPaymentException('An unexpected error occurred during PayPal capture: '.$e->getMessage());
            }
            $finalStatus = $captureRes->result->status ?? 'FAILED';
        } elseif ($status != 'COMPLETED') {
            throw new WrongPaymentException('PayPal order was not approved for capture. Status: '.$status);
        } else {
            $finalStatus = $status;
            $captureRes = $orderRes;
        }
        if ($finalStatus !== 'COMPLETED') {
            throw new WrongPaymentException('PayPal payment was not completed. Final status: '.$finalStatus);
        }
        $capture = $captureRes->result->purchase_units[0]->payments->captures[0] ?? null;
        if (! $capture) {
            throw new WrongPaymentException('Missing capture data from PayPal response.');
        }

        $invoice->update([
            'external_id' => $capture->id,
            'fees' => $capture->seller_receivable_breakdown->paypal_fee->value ?? null,
        ]);
        $invoice->attachMetadata('used_payment_method', $sourceDTO->id);
        $invoice->complete();

        return new GatewayPayInvoiceResultDTO(true, 'Done', $invoice, $sourceDTO);
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            $url = $_ENV['PAYPAL_SANDBOX'] == 'sandbox' ? 'https://www.sandbox.paypal.com/unifiedtransactions/details/payment/' : 'https://www.paypal.com/unifiedtransactions/details/payment/';

            return $url.$invoice->external_id;
        }

        return null;
    }

    public function getSource(Customer $customer, string $sourceId): ?PaymentMethodSourceDTO
    {
        return collect($this->getSources($customer))->where('id', $sourceId)->first();
    }

    public function getSources(Customer $customer): array
    {
        $customer_id = $customer->getMetadata('paypal_customer_id');
        if (! $customer_id) {
            return [];
        }
        $req = new HttpRequest('/v3/vault/payment-tokens?customer_id='.$customer_id, 'GET');
        try {
            $resp = $this->executeClient($req);
        } catch (\Exception $e) {
            return [];
        }

        return collect($resp->result->payment_tokens ?? [])->map(function ($token) use ($customer) {
            return new PaymentMethodSourceDTO(
                $token->id,
                'PayPal',
                '****',
                '-',
                '-',
                $customer->id,
                self::UUID,
                $token->payment_source->paypal->email_address
            );
        })->toArray();
    }

    private function executeClient(HttpRequest $request)
    {
        $client = $this->getClient();
        try {
            $request->headers['Authorization'] = 'Basic '.$client->environment->authorizationString();
            $request->headers['Content-Type'] = 'application/json';

            return $client->execute($request);
        } catch (\Exception $e) {
            throw new WrongPaymentException('PayPal API request failed: '.$e->getMessage());
        }
    }
}
