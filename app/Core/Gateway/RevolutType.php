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
use Illuminate\Support\Facades\Log;

class RevolutType extends AbstractGatewayType
{
    const UUID = 'revolut';

    protected string $name = 'Revolut';

    protected string $uuid = self::UUID;

    protected string $image = 'revolut-icon.png';

    protected string $icon = 'bi bi-bank';

    public function createPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        $token = $this->getAccessToken();

        $accountId = $this->getAccountId($token);

        $payload = [
            'request_id' => uniqid('revolut_', true),
            'account_id' => $accountId,
            'receiver' => [
                'counterparty_id' => env('REVOLUT_COUNTERPARTY_ID'),
                'account_id' => env('REVOLUT_COUNTERPARTY_ACCOUNT_ID')
            ],
            'amount' => (float) $invoice->total,
            'currency' => $invoice->currency,
            'reference' => 'Invoice #' . $invoice->id,
        ];

        $response = Http::withToken($token)
            ->post($this->getApiDomain() . '/api/1.0/payment', $payload);

        if (!$response->successful()) {
            throw new WrongPaymentException('Erreur Revolut: ' . $response->body());
        }

        $invoice->update(['external_id' => $response->json('id')]);
        $invoice->complete();

        return redirect($dto->returnUri);
    }

    public function processPayment(Invoice $invoice, Gateway $gateway, Request $request, GatewayUriDTO $dto)
    {
        return redirect()->route('front.invoices.show', $invoice);
    }

    public function notification(Gateway $gateway, Request $request)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();

        Log::info('Revolut Webhook reçu', [
            'body' => $payload,
            'headers' => $headers
        ]);

        $data = json_decode($payload, true);

        if (!isset($data['event_type']) || !isset($data['data'])) {
            return response()->json(['error' => 'Payload invalide'], 400);
        }

        if ($data['event_type'] === 'transaction.completed') {
            $transaction = $data['data'];

            $reference = $transaction['reference'] ?? null;
            $id = null;
            if (preg_match('/Invoice #(\d+)/', $reference, $matches)) {
                $id = $matches[1];
            }

            if (!$id) {
                return response()->json(['error' => 'Facture non reconnue'], 400);
            }

            $invoice = Invoice::find($id);
            if (!$invoice) {
                return response()->json(['error' => 'Facture introuvable'], 404);
            }

            $invoice->update(['external_id' => $transaction['id']]);
            $invoice->complete();

            return response()->json(['success' => 'Facture marquée comme payée']);
        }

        return response()->json(['status' => 'event ignoré']);
    }

    public function saveConfig(array $data)
    {
        EnvEditor::updateEnv([
            'REVOLUT_CLIENT_ID' => $data['client_id'],
            'REVOLUT_CLIENT_SECRET' => $data['client_secret'],
            'REVOLUT_COUNTERPARTY_ID' => $data['counterparty_id'],
            'REVOLUT_COUNTERPARTY_ACCOUNT_ID' => $data['counterparty_account_id'],
            'REVOLUT_SANDBOX' => $data['sandbox'] == 'sandbox' ? 'true' : 'false',
        ]);
    }

    public function validate(): array
    {
        return [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'counterparty_id' => 'required|string',
            'counterparty_account_id' => 'required|string',
        ];
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->post($this->getApiDomain() . '/api/1.0/auth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => env('REVOLUT_CLIENT_ID'),
            'client_secret' => env('REVOLUT_CLIENT_SECRET')
        ]);

        if (!$response->successful()) {
            throw new WrongPaymentException('Impossible de récupérer le token Revolut: ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function getAccountId(string $token): string
    {
        $response = Http::withToken($token)->get('https://b2b.revolut.com/api/1.0/accounts');

        if (!$response->successful()) {
            throw new WrongPaymentException('Impossible de récupérer les comptes Revolut: ' . $response->body());
        }

        return $response->json()[0]['id'];
    }

    public function getPaymentDetailsUrl(Invoice $invoice): ?string
    {
        return null;
    }

    public function configForm(array $context = [])
    {
        return view('admin.settings.store.gateways.revolut', $context);
    }

    private function getApiDomain(): string
    {
        return env('REVOLUT_SANDBOX', false) ? 'https://sandbox.b2b.revolut.com' : 'https://b2b.revolut.com';
    }
}
