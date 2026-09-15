<?php

namespace App\Services\Billing;

use App\Contracts\Billing\ElectronicInvoiceRendererInterface;
use App\DTO\Billing\Electronic\GeneratedElectronicInvoice;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use DOMDocument;
use horstoeko\zugferd\quick\ZugferdQuickDescriptor;
use horstoeko\zugferd\ZugferdDocumentPdfMerger;
use horstoeko\zugferd\ZugferdDocumentPdfReader;
use horstoeko\zugferd\ZugferdXsdValidator;

class FacturXRenderer implements ElectronicInvoiceRendererInterface
{
    public function render(Invoice|CreditNote $document): GeneratedElectronicInvoice
    {
        $credit = $document instanceof CreditNote;
        $invoice = $credit ? $document->invoice()->with(['customer', 'items'])->firstOrFail() : $document->loadMissing(['customer', 'items']);
        $snapshot = $invoice->billing_snapshot ?: app(FiscalProfileService::class)->snapshot($invoice);
        $seller = $snapshot['seller'] ?? [];
        $buyer = $snapshot['buyer'] ?? [];
        $sellerAddress = is_array($seller['address'] ?? null) ? $seller['address'] : [];
        $buyerAddress = $buyer['address'] ?? [];
        $this->assertParty($seller, $sellerAddress, 'vendeur');
        $this->assertParty($buyer, $buyerAddress, 'acheteur');

        $builder = ZugferdQuickDescriptor::doCreateNew();
        $issuedAt = ($credit ? $document->created_at : $invoice->issued_at) ?? $document->created_at ?? now();
        if ($credit) {
            $builder->doCreateCreditMemo($document->identifier(), $issuedAt->toDateTime(), strtoupper($document->currency), $invoice->identifier())
                ->doSetInvoiceReferencedDocument($invoice->identifier(), optional($invoice->issued_at)->toDateTime());
        } else {
            $builder->doCreateInvoice($invoice->identifier(), $issuedAt->toDateTime(), strtoupper($invoice->currency));
        }

        $builder->doSetSeller((string) $seller['legal_name'], (string) $sellerAddress['zipcode'], (string) $sellerAddress['city'], $this->street($sellerAddress), strtoupper((string) $sellerAddress['country']), $seller['siret'] ?? $seller['siren'] ?? null)
            ->doSetBuyer((string) ($buyer['legal_name'] ?: trim($invoice->customer->firstname.' '.$invoice->customer->lastname)), (string) $buyerAddress['zipcode'], (string) $buyerAddress['city'], $this->street($buyerAddress), strtoupper((string) $buyerAddress['country']), $invoice->customer->uuid ?? null);
        if (filled($seller['vat_number'] ?? null)) {
            $builder->doAddSellerTaxRegistration((string) $seller['vat_number'], 'VA');
        }
        if (filled($buyer['vat_number'] ?? null)) {
            $builder->doAddBuyerTaxRegistration((string) $buyer['vat_number'], 'VA');
        }

        $lines = $credit ? [[
            'name' => 'Avoir sur facture '.$invoice->identifier(), 'quantity' => 1,
            'unit_price_ht' => abs((float) $document->amount), 'vat_rate' => $document->amount != 0 ? abs((float) $document->tax / (float) $document->amount * 100) : 0,
            'tax_category' => (float) $document->tax === 0.0 ? 'zero' : 'standard', 'tax_exemption_reason' => null,
        ]] : $invoice->items->map(fn ($item) => $item->toArray() + ['discount_total' => (float) $item->discountTotal()])->all();

        $computedHt = 0.0;
        $computedTax = 0.0;
        foreach ($lines as $index => $line) {
            $rate = (float) ($line['vat_rate'] ?? 0);
            $category = $this->taxCategory((string) ($line['tax_category'] ?? 'standard'), $rate);
            if ($rate === 0.0 && $category === 'E' && blank($line['tax_exemption_reason'] ?? null)) {
                throw new \RuntimeException('Un motif d’exonération est requis pour la ligne '.($index + 1).'.');
            }
            $quantity = max(1, (float) ($line['quantity'] ?? 1));
            $unit = $credit ? (float) $line['unit_price_ht'] : (float) ($line['unit_price_ht'] ?? 0) + (float) ($line['unit_setup_ht'] ?? 0);
            $discount = (float) ($line['discount_total'] ?? 0);
            $base = round($unit * $quantity - $discount, 2);
            $computedHt += $base;
            $computedTax += round($base * $rate / 100, 2);
            $builder->doAddTradeLineItem((string) ($index + 1), (string) ($line['name'] ?? 'Ligne'), $unit, $quantity, 'C62', -$discount, $discount > 0 ? 'Remise commerciale' : '', $category, 'VAT', $rate);
        }

        if (! $credit && (float) $invoice->balance > 0) {
            $firstRate = (float) ($lines[0]['vat_rate'] ?? 0);
            $firstCategory = $this->taxCategory((string) ($lines[0]['tax_category'] ?? 'standard'), $firstRate);
            $builder->doAddTradeAllowanceCharge(-(float) $invoice->balance, 'Utilisation du solde client', $firstCategory, 'VAT', $firstRate);
            $computedHt -= (float) $invoice->balance;
            $computedTax -= round((float) $invoice->balance * $firstRate / 100, 2);
        }

        $expectedHt = abs((float) ($credit ? $document->amount : $invoice->subtotal));
        $expectedTax = abs((float) ($credit ? $document->tax : $invoice->tax));
        if (abs($computedHt - $expectedHt) > 0.01 || abs($computedTax - $expectedTax) > 0.01) {
            throw new \RuntimeException(sprintf('Totaux Factur-X incohérents (calcul %.2f + %.2f, facture %.2f + %.2f).', $computedHt, $computedTax, $expectedHt, $expectedTax));
        }
        if (! $credit && $invoice->due_date) {
            $builder->doSetPaymentTerms('Paiement à échéance', $invoice->due_date->toDateTime());
        }

        $xml = $builder->getContent();
        $dom = new DOMDocument;
        if (! $dom->loadXML($xml, LIBXML_NONET)) {
            throw new \RuntimeException('Le XML Factur-X généré est invalide.');
        }
        $visualPdf = $credit ? $document->generatePdf(false)->output() : $invoice->generatePdf(false)->output();
        $pdf = (new ZugferdDocumentPdfMerger($xml, $visualPdf))->generateDocument()->downloadString();
        $embeddedXml = ZugferdDocumentPdfReader::getXmlFromContent($pdf);
        if (hash('sha256', $embeddedXml) !== hash('sha256', $xml)) {
            throw new \RuntimeException('La pièce jointe factur-x.xml du PDF ne correspond pas au XML généré.');
        }
        $reader = ZugferdDocumentPdfReader::readAndGuessFromContent($pdf);
        $validator = (new ZugferdXsdValidator($reader))->validate();
        if ($validator->hasValidationErrors()) {
            throw new \RuntimeException('Le XML Factur-X ne respecte pas son schéma : '.implode(' ', $validator->validationErrors()));
        }
        $sha = hash('sha256', $pdf);

        return new GeneratedElectronicInvoice('EN16931', $xml, $pdf, $sha, [
            'number' => $document->identifier(), 'type' => $credit ? 'credit_note' : 'invoice',
            'xml_sha256' => hash('sha256', $xml), 'pdf_sha256' => $sha,
            'original_invoice' => $credit ? $invoice->identifier() : null,
        ]);
    }

    private function assertParty(array $party, array $address, string $label): void
    {
        foreach (['legal_name'] as $field) {
            if (blank($party[$field] ?? null)) {
                throw new \RuntimeException("Le nom légal {$label} est requis.");
            }
        }
        foreach (['address', 'zipcode', 'city', 'country'] as $field) {
            if (blank($address[$field] ?? null)) {
                throw new \RuntimeException("L’adresse structurée {$label} est incomplète ({$field}).");
            }
        }
    }

    private function street(array $address): string
    {
        return trim(($address['address'] ?? '').' '.($address['address2'] ?? ''));
    }

    private function taxCategory(string $category, float $rate): string
    {
        return match ($category) {
            'exempt', 'reverse_charge' => 'E', 'zero' => 'Z', default => $rate > 0 ? 'S' : 'Z'
        };
    }
}
