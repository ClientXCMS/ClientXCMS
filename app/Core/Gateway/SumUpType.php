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

class SumUpType extends AbstractGatewayType
{
    const UUID = 'sumup';

    const API_URL = 'https://api.sumup.com/v0.1';

    protected string $name = 'SumUp';

    protected string $uuid = self::UUID;

    protected string $image = 'sumup-icon.png';

    protected string $icon = 'bi bi-credit-card-2-front';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post(self::API_URL . '/checkouts', [
                    'checkout_reference' => 'INV-' . $invoice->id . '-' . uniqid(),
                    'amount' => (float) $invoice->total,
                    'currency' => strtoupper($invoice->currency),
                    'merchant_code' => env('SUMUP_MERCHANT_CODE'),
                    'description' => __('global.invoice') . ' #' . $invoice->id,
                    'return_url' => $dto->returnUri,
                ]);

            if ($response->failed()) {
                $error = $response->json('error_message') ?? $response->body();
                throw new WrongPaymentException('SumUp error: ' . $error);
            }

            $checkout = $response->json();
            $checkoutId = $checkout['id'];

            $invoice->update(['external_id' => $checkoutId]);

            // Build hosted checkout URL
            $checkoutUrl = 'https://api.sumup.com/v0.1/checkouts/' . $checkoutId;

            return redirect($checkoutUrl, 303);
        } catch (\Exception $e) {
            throw new WrongPaymentException('SumUp payment creation failed: ' . $e->getMessage());
        }
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        // Check checkout status on return
        if ($invoice->external_id) {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->get(self::API_URL . '/checkouts/' . $invoice->external_id);

                if ($response->successful()) {
                    $checkout = $response->json();
                    $status = $checkout['status'] ?? null;

                    if ($status === 'PAID') {
                        $transactionId = $checkout['transaction_id'] ?? null;
                        if ($transactionId) {
                            $invoice->update(['external_id' => $transactionId]);
                        }
                        $invoice->complete();
                        return redirect()->route('front.invoices.show', $invoice)->with('success', __('store.checkout.success'));
                    }
                }
            } catch (\Exception $e) {
                logger()->error('SumUp processPayment error: ' . $e->getMessage());
            }
        }

        return redirect()->route('front.invoices.show', $invoice);
    }

    public function validate(): array
    {
        return [
            'api_key' => 'required|string',
            'merchant_code' => 'required|string',
        ];
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'SUMUP_API_KEY' => $data['api_key'],
            'SUMUP_MERCHANT_CODE' => $data['merchant_code'],
        ]);
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.sumup', $context);
    }

    private function getHeaders(): array
    {
        $apiKey = env('SUMUP_API_KEY');
        if (!$apiKey) {
            throw new WrongPaymentException('SumUp API key not configured');
        }

        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            return 'https://me.sumup.com/transactions/' . $invoice->external_id;
        }
        return null;
    }
}
