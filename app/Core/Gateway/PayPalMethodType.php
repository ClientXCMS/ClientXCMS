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
use App\Helpers\EnvEditor;
use App\Models\Billing\Gateway;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Str;

class PayPalMethodType extends AbstractGatewayType
{
    const UUID = 'paypal_method';

    protected string $name = 'PayPal Method';

    protected string $uuid = self::UUID;

    protected string $image = 'paypal-icon.png';

    protected string $icon = 'bi bi-paypal';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {

        $attributes = [
            'cmd' => '_xclick',
            'charset' => 'utf-8',
            'business' => env('PAYPAL_EMAIL'),
            'amount' => $invoice->total,
            'currency_code' => strtoupper($invoice->currency),
            'item_name' => 'Invoice #'.$invoice->id,
            'quantity' => 1,
            'no_shipping' => 1,
            'no_note' => 1,
            'return' => $dto->returnUri,
            'cancel_return' => $dto->cancelUri,
            'notify_url' => $dto->notificationUri,
            'custom' => $invoice->id,
            'bn' => 'CLIENTXCMS',
        ];
        $url = $this->getRedirectUri().'?'.http_build_query($attributes);

        return redirect()->to($url);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        return redirect()->route('front.invoices.show', $invoice)->with('success', __('store.checkout.success'));
    }

    public function notification(Gateway $gateway, Request $request)
    {

        $data = ['cmd' => '_notify-validate'] + $request->all();

        $response = \Http::asForm()->post($this->getIpn(), $data);

        if ($response->body() !== 'VERIFIED') {
            return response()->json('Invalid response from PayPal', 401);
        }

        $paymentId = $request->input('txn_id');
        $amount = $request->input('mc_gross');
        $currency = $request->input('mc_currency');
        $status = $request->input('payment_status');
        $caseType = $request->input('case_type');
        $receiverEmail = Str::lower((string) $request->input('receiver_email'));

        if ($status === 'Canceled_Reversal' || $caseType !== null) {
            return response('', 200);
        }

        $merchantEmail = Str::lower((string) env('PAYPAL_EMAIL'));
        if ($merchantEmail === '' || $receiverEmail !== $merchantEmail) {
            return $this->ignoreNotification('receiver mismatch', $paymentId);
        }

        $invoice = Invoice::find($request->input('custom'));
        if ($invoice === null || $invoice->paymethod !== $gateway->uuid) {
            return $this->ignoreNotification('unknown invoice', $paymentId);
        }
        if ($currency !== $invoice->currency) {
            return $this->ignoreNotification('currency mismatch', $paymentId);
        }

        if ($status === 'Reversed') {
            $parentTxnId = $request->input('parent_txn_id');
            if ($parentTxnId === null || $parentTxnId !== $invoice->external_id) {
                return $this->ignoreNotification('reversal of another transaction', $paymentId);
            }
            $invoice->refund();

            return response('', 200);
        }

        if ($status !== 'Completed') {
            return response('', 200);
        }

        if ($amount < $invoice->total) {
            return $this->ignoreNotification('amount mismatch', $paymentId);
        }
        $invoice->update(['external_id' => $paymentId, 'fees' => $request->input('mc_fee')]);
        $invoice->complete();

        return response('', 200);

    }

    private function ignoreNotification(string $reason, mixed $paymentId)
    {
        logger()->warning('[PayPal] IPN ignored: '.$reason, ['txn_id' => $paymentId]);

        return response('', 200);
    }

    public function validate(): array
    {
        return [
            'paypal_email' => 'required|email',
            'sandbox' => 'required',
        ];
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv(['PAYPAL_EMAIL' => $data['paypal_email'], 'PAYPAL_SANDBOX' => $data['sandbox'] == 'sandbox' ? 'true' : 'false']);
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.paypal', $context);
    }

    private function getRedirectUri(): string
    {
        return $_ENV['PAYPAL_SANDBOX'] == 'sandbox' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    }

    private function getIpn(): string
    {
        return $_ENV['PAYPAL_SANDBOX'] == 'sandbox' ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr';
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        if ($invoice->external_id) {
            $url = $_ENV['PAYPAL_SANDBOX'] == 'sandbox' ? 'https://www.sandbox.paypal.com/unifiedtransactions/details/payment/' : 'https://www.paypal.com/unifiedtransactions/details/payment/';

            return $url.$invoice->external_id;
        }

        return null;
    }
}
