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
namespace App\Core\Gateway;

use App\Abstracts\AbstractGatewayType;
use App\Abstracts\PaymentMethodSourceDTO;
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Exceptions\WrongPaymentException;
use App\Helpers\EnvEditor;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Subscription;
use Illuminate\Http\Request;
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
        $client = $this->getClient();
        $discounts = $invoice->getDiscountTotal();
        $items = $invoice->items->map(function (InvoiceItem $item) use ($invoice) {
            $discount = 0;
            if ($item->hasDiscount()) {
                $discount = $item->getDiscount()->sub_price + $item->getDiscount()->sub_setup;
            }
            $price = $item->unit_price_ht + $item->unit_setup_ht;
            $price = $price * $item->quantity;

            return [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'category' => 'DIGITAL_GOODS',
                'sku' => $item->id,
                'description' => $item->description,
                'unit_amount' => [
                    'currency_code' => $invoice->currency,
                    'value' => (string) (number_format((float) ($price - $discount), 2)),
                ],
            ];
        })->toArray();
        $order = new OrdersCreateRequest;
        $order->prefer('return=representation');
        $order->headers['Authorization'] = 'Basic '.$client->environment->authorizationString();
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
                                'value' => (string) number_format($invoice->total, 2),
                            ],
                            'tax_total' => [
                                'currency_code' => $invoice->currency,
                                'value' => (string) number_format($invoice->tax, 2),
                            ],
                        ],
                    ],
                    'items' => $items,
                    'discount' => [
                        'currency_code' => $invoice->currency,
                        'value' => (string) number_format($discounts, 2),
                    ],
                ],
            ],
        ];
        try {
            $response = $client->execute($order);
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
            $request->headers['Authorization'] = 'Basic '.$this->getClient()->environment->authorizationString();
            $responsePayPal = $this->getClient()->execute($request);
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

    public function createSubscription(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto): ?Subscription
    {
        return null;
    }

    public function notification(Gateway $gateway, Request $request)
    {
        $type = $this->verifyNotification($request);

        return parent::notification($gateway, $request); // TODO: Change the autogenerated stub
    }

    public function cancelSubscription(Subscription $subscription): ?Subscription
    {
        return parent::cancelSubscription($subscription); // TODO: Change the autogenerated stub
    }

    private function verifyNotification(Request $request)
    {
        $client = $this->getClient();
        $req = new HttpRequest('v1/notifications/verify-webhook-signature', 'POST');
        $headers = [
            'transmission_id' => $request->header('PayPal-Transmission-ID'),
            'transmission_time' => $request->header('PayPal-Transmission-Time'),
            'cert_url' => $request->header('PayPal-Cert-Url'),
            'auth_algo' => $request->header('PayPal-Auth-Algo'),
            'transmission_sig' => $request->header('PayPal-Transmission-Sig'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'webhook_event' => $request->json()->all(),
        ];
        $req->headers = $headers;
        $response = $client->execute($req);
        $result = $response->result;
        if ($result->verification_status !== 'SUCCESS') {
            logger()->error('Invalid PayPal webhook signature.', $response->result);

            abort(400, 'Invalid PayPal webhook signature.');
        }

        return $response->event_type;
    }

    public function sourceForm(): string
    {
        return '';

        return view('admin.settings.store.gateways.paypal-source')->render();
    }

    public function addSource(Request $request): ?PaymentMethodSourceDTO
    {
        $client = $this->getClient();
        $request = new OrdersCreateRequest;
        $request->prefer('return=representation');
        $request->headers['Authorization'] = 'Basic '.$client->environment->authorizationString();
        $request->body = [
            'intent' => 'AUTHORIZE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => '1.00',
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => setting('app_name'),
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'CONTINUE',
                'cancel_url' => route('front.payment-methods.index'),
                'return_url' => route('gateways.source.return', ['gateway' => self::UUID]),
            ],
        ];
        $response = $client->execute($request);
        $result = $response->result;
        $approveLink = collect($result->links)->first(function ($link) {
            return $link->rel === 'approve';
        });
        header('Location: '.$approveLink->href);
        exit;
        redirect($approveLink->href);

        return null;
    }

    public function sourceReturn(Request $request): ?PaymentMethodSourceDTO
    {
        $token = $request->query('token');
        $payerId = $request->query('PayerID');
        $client = $this->getClient();
        $request = new OrdersCaptureRequest($token);
        $request->prefer('return=representation');
        $request->headers['Authorization'] = 'Basic '.$client->environment->authorizationString();
        $responsePayPal = $client->execute($request);
        $result = $responsePayPal->result;
        if ($result->status === 'COMPLETED') {
            return new PaymentMethodSourceDTO($result->id, $result->payer->email_address);
        }

        return null;
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            $url = $_ENV['PAYPAL_SANDBOX'] == 'sandbox' ? 'https://www.sandbox.paypal.com/unifiedtransactions/details/payment/' : 'https://www.paypal.com/unifiedtransactions/details/payment/';
            return $url . $invoice->external_id;
        }

        return null;
    }
}
