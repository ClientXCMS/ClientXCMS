<?php

namespace App\Addons\ChorusPro;

use App\Contracts\Billing\ElectronicExchangeProviderInterface;
use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\DTO\Billing\Electronic\PaymentReportPayload;
use App\DTO\Billing\Electronic\ProviderStatusResult;
use App\DTO\Billing\Electronic\ProviderSubmissionResult;
use App\DTO\Billing\Electronic\TransactionReportPayload;
use App\Models\Billing\ElectronicDocument;
use App\Models\Billing\EReportingPeriod;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ChorusProExchangeProvider implements ElectronicExchangeProviderInterface
{
    public function key(): string
    {
        return 'chorus-pro';
    }

    public function capabilities(): array
    {
        return ['einvoicing', 'b2g', 'status_tracking'];
    }

    public function isConfigured(): bool
    {
        return filter_var(setting('chorus_pro_enabled', false), FILTER_VALIDATE_BOOL)
            && filled(setting('chorus_pro_client_id'))
            && filled(setting('chorus_pro_client_secret'))
            && filled(setting('chorus_pro_account'));
    }

    public function diagnostic(): array
    {
        return ['authenticated' => filled($this->token()), 'environment' => setting('chorus_pro_environment', 'qualification')];
    }

    public function submitInvoice(ElectronicDocument $document, GeneratedElectronicInvoice $artifact): ProviderSubmissionResult
    {
        $source = $document->documentable;
        $invoice = $source instanceof \App\Models\Billing\CreditNote ? $source->invoice : $source;
        $buyer = data_get($invoice->billing_snapshot, 'buyer', []);
        if (! data_get($buyer, 'is_public_entity') || ! preg_match('/^\d{14}$/', (string) data_get($buyer, 'siret'))) {
            throw new \RuntimeException('Le destinataire Chorus Pro doit être une entité publique avec un SIRET valide.');
        }

        $response = $this->client()->post('/factures/v1/deposer/flux', [
            'fichierFlux' => base64_encode($artifact->pdf),
            'nomFichier' => $source->identifier().'.pdf',
            'syntaxeFlux' => 'IN_DP_E1_CII_FACTURX',
            'avecSignature' => false,
        ])->throw()->json();
        $externalId = (string) ($response['numeroFluxDepot'] ?? '');
        if ($externalId === '') {
            throw new \RuntimeException('Chorus Pro n’a retourné aucun numéro de flux de dépôt.');
        }

        return new ProviderSubmissionResult($externalId, ElectronicDocument::STATUS_SUBMITTED, $response);
    }

    public function fetchStatus(ElectronicDocument $document): ProviderStatusResult
    {
        if (blank($document->provider_document_id)) {
            return new ProviderStatusResult($document->status, []);
        }
        $response = $this->client()->post('/transverses/v1/consulterCR', ['numeroFluxDepot' => $document->provider_document_id])->throw()->json();
        $external = strtoupper((string) ($response['etatCourantDepot'] ?? $response['statut'] ?? ''));
        $status = $this->normalizeStatus($external);

        return new ProviderStatusResult($status, $response);
    }

    public function normalizeStatus(string $external): string
    {
        $external = strtoupper($external);

        return match (true) {
            str_contains($external, 'REJET'), str_contains($external, 'REFUS') => ElectronicDocument::STATUS_REJECTED,
            str_contains($external, 'INTEGRE'), str_contains($external, 'MISE_A_DISPOSITION'), str_contains($external, 'MANDATE') => ElectronicDocument::STATUS_DELIVERED,
            default => ElectronicDocument::STATUS_SUBMITTED,
        };
    }

    public function submitTransactionReport(EReportingPeriod $period, TransactionReportPayload $payload): ProviderSubmissionResult
    {
        throw new \LogicException('Chorus Pro ne prend pas en charge l’e-reporting général de CLIENTXCMS.');
    }

    public function submitPaymentReport(EReportingPeriod $period, PaymentReportPayload $payload): ProviderSubmissionResult
    {
        throw new \LogicException('Chorus Pro ne prend pas en charge l’e-reporting général de CLIENTXCMS.');
    }

    private function client(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Le raccordement Chorus Pro n’est pas configuré.');
        }

        return Http::baseUrl(rtrim((string) setting('chorus_pro_api_url', 'https://sandbox-api.piste.gouv.fr/cpro'), '/'))
            ->withToken($this->token())
            ->withHeaders(['cpro-account' => base64_encode($this->secret('chorus_pro_account'))])
            ->acceptJson()->asJson()->timeout(max(1, (int) setting('chorus_pro_timeout', 30)))->retry(2, 500, throw: false);
    }

    private function token(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Le raccordement Chorus Pro n’est pas configuré.');
        }
        $cacheKey = 'chorus-pro:oauth:'.hash('sha256', $this->secret('chorus_pro_client_id'));

        return Cache::remember($cacheKey, 3300, function (): string {
            $response = Http::asForm()->timeout(max(1, (int) setting('chorus_pro_timeout', 30)))->post((string) setting('chorus_pro_oauth_url', 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => $this->secret('chorus_pro_client_id'),
                'client_secret' => $this->secret('chorus_pro_client_secret'),
                'scope' => 'openid',
            ])->throw()->json();

            return (string) ($response['access_token'] ?? throw new \RuntimeException('PISTE n’a retourné aucun jeton OAuth.'));
        });
    }

    private function secret(string $key): string
    {
        $value = (string) setting($key);
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
