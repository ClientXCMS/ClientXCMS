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
        $receiverEmail = Str::lower($request->input('receiver_email'));

        if ($status === 'Canceled_Reversal' || $caseType !== null) {
            return response()->noContent();
        }
        $invoice = Invoice::find($request->input('custom'));
        if ($invoice == null) {
            return response()->noContent();
        }
        $invoice->update(['external_id' => $paymentId]);
        if ($status === 'Reversed' || $status === 'Pending') {
            if ($status === 'Reversed') {
                $invoice->refund();
            }

            return response()->noContent();
        }

        if ($status !== 'Completed') {
            return response()->json([
                'success' => false,
                'error' => 'Invalid payment status',
            ]);
        }

        if ($currency !== $invoice->currency || $amount < $invoice->total) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid amount/currency',
            ]);
        }

        $email = Str::lower(env('PAYPAL_EMAIL'));

        if ($receiverEmail !== $email) {
            logger()->warning("[PayPal] Invalid email for #{$paymentId}: expected {$email} but got {$receiverEmail}.");

            return response()->json([
                'success' => false,
                'error' => 'Invalid receiver email',
            ]);
        }
        $invoice->update(['external_id' => $paymentId, 'fees' => $request->input('mc_fee')]);
        $invoice->complete();

        return response()->json(['success' => true, 'message' => 'Payment completed']);

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
            return $url . $invoice->external_id;
        }

        return null;
    }
}
