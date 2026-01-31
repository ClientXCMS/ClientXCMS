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
use App\DTO\Core\Gateway\GatewayUriDTO;
use App\Exceptions\WrongPaymentException;
use App\Helpers\EnvEditor;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SquareType extends AbstractGatewayType
{
    const UUID = 'square';

    const API_URL_PRODUCTION = 'https://connect.squareup.com/v2';

    const API_URL_SANDBOX = 'https://connect.squareupsandbox.com/v2';

    protected string $name = 'Square';

    protected string $uuid = self::UUID;

    protected string $image = 'square-icon.png';

    protected string $icon = 'bi bi-square';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->getApiUrl().'/online-checkout/payment-links', [
                    'idempotency_key' => uniqid('inv_'.$invoice->id.'_', true),
                    'quick_pay' => [
                        'name' => __('global.invoice').' #'.$invoice->id,
                        'price_money' => [
                            'amount' => (int) ($invoice->total * 100),
                            'currency' => strtoupper($invoice->currency),
                        ],
                        'location_id' => env('SQUARE_LOCATION_ID'),
                    ],
                    'checkout_options' => [
                        'redirect_url' => $dto->returnUri,
                    ],
                ]);

            if ($response->failed()) {
                $errors = $response->json('errors');
                throw new WrongPaymentException('Square error: '.json_encode($errors));
            }

            $paymentLink = $response->json('payment_link');
            $invoice->update(['external_id' => $paymentLink['id']]);

            return redirect($paymentLink['url'], 303);
        } catch (\Exception $e) {
            throw new WrongPaymentException('Square payment creation failed: '.$e->getMessage());
        }
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        // Check payment link status on return
        if ($invoice->external_id) {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->get($this->getApiUrl().'/online-checkout/payment-links/'.$invoice->external_id);

                if ($response->successful()) {
                    $paymentLink = $response->json('payment_link');
                    $orderId = $paymentLink['order_id'] ?? null;

                    if ($orderId) {
                        $orderResponse = Http::withHeaders($this->getHeaders())
                            ->get($this->getApiUrl().'/orders/'.$orderId);

                        if ($orderResponse->successful()) {
                            $order = $orderResponse->json('order');
                            if (($order['state'] ?? null) === 'COMPLETED') {
                                $invoice->complete();

                                return redirect()->route('front.invoices.show', $invoice)->with('success', __('store.checkout.success'));
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Square processPayment error: '.$e->getMessage());
            }
        }

        return redirect()->route('front.invoices.show', $invoice);
    }

    public function validate(): array
    {
        return [
            'access_token' => 'required|string',
            'location_id' => 'required|string',
            'environment' => 'required|in:sandbox,production',
            'webhook_signature_key' => 'nullable|string',
        ];
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'SQUARE_ACCESS_TOKEN' => $data['access_token'],
            'SQUARE_LOCATION_ID' => $data['location_id'],
            'SQUARE_ENVIRONMENT' => $data['environment'],
            'SQUARE_WEBHOOK_SIGNATURE_KEY' => $data['webhook_signature_key'] ?? '',
        ]);
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.square', $context);
    }

    private function getApiUrl(): string
    {
        $environment = env('SQUARE_ENVIRONMENT', 'sandbox');

        return $environment === 'production' ? self::API_URL_PRODUCTION : self::API_URL_SANDBOX;
    }

    private function getHeaders(): array
    {
        $accessToken = env('SQUARE_ACCESS_TOKEN');
        if (! $accessToken) {
            throw new WrongPaymentException('Square access token not configured');
        }

        return [
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
            'Square-Version' => '2024-01-18',
        ];
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            $environment = env('SQUARE_ENVIRONMENT', 'sandbox');
            $baseUrl = $environment === 'production'
                ? 'https://squareup.com/dashboard/sales/transactions/'
                : 'https://squareupsandbox.com/dashboard/sales/transactions/';

            return $baseUrl.$invoice->external_id;
        }

        return null;
    }
}
