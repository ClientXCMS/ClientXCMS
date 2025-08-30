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


namespace App\DTO\Store;

use App\Models\Billing\Upgrade;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;
use App\Services\Store\TaxesService;
use Carbon\Carbon;

class UpgradeDTO
{
    private Service $service;

    const MODE_NO_INVOICE = 'no_invoice';

    const MODE_INVOICE = 'invoice';

    public function __construct(Service $service)
    {
        $this->service = $service;
        if ($this->service->product_id == null) {
            throw new \Exception('Service has no product');
        }
    }

    public static function mustForceRenewal(Service $service)
    {
        if ($service->expires_at == null) {
            return false;
        }
        // si le service est en essai gratuit, on force le renouvellement du service suivant pour ajouter 1 mois
        if ($service->trial_ends_at != null) {
            return true;
        }
        $datetime = new \DateTime;
        $diff = $datetime->diff($service->expires_at);
        $diffInDays = $diff->d;

        return $diffInDays <= setting('minimum_days_to_force_renewal_with_upgrade', 3);
    }

    public function generatePrice(Product $product): ProductPriceDTO
    {
        // Le prix de l'upgrade est nulle car le service va être renouvelé avec le mustForceRenewal
        if ($this->service->trial_ends_at != null) {
            return new ProductPriceDTO(0, 0, $this->service->currency, $this->service->billing, 0);
        }
        $billing = $this->service->billing;
        if ($billing == 'onetime') {
            return $product->getPriceByCurrency($this->service->currency, 'onetime');
        }
        if ($billing == 'weekly') {
            return $product->getPriceByCurrency($this->service->currency, 'monthly');
        }
        if ($this->service->isOneTime()) {
            return $product->getPriceByCurrency($this->service->currency, $billing);
        }
        $price = $product->getPriceByCurrency($this->service->currency, $billing);
        $datetime = new \DateTime;
        $diff = $datetime->diff($this->service->expires_at);
        $diffInMonths = $diff->m;
        $diffInDays = $diff->d;
        if ($diffInMonths == 0) {
            $prorata = round(($diffInDays / $datetime->format('t')) * ($price->price_ht - $this->service->getBillingPrice()->price_ht), 2);
        } else {
            $prorata = round(($diffInMonths * 30 + $diffInDays) / 30 * ($price->price_ht - $this->service->getBillingPrice()->price_ht), 2);
        }
        $firstpayment = $prorata;
        if ($diffInDays <= setting('minimum_days_to_force_renewal_with_upgrade', 3)) {
            $firstpayment = $prorata + $price->price_ht;
        }
        if (setting('add_setupfee_on_upgrade', 'true') == 'true') {
            return new ProductPriceDTO($price->priceTTC(), $price->setupTTC(), $price->currency, $price->recurring, $firstpayment);
        }

        return new ProductPriceDTO($price->priceTTC(), 0, $price->currency, $price->recurring, $firstpayment);
    }

    public function createUpgrade(Product $newProduct)
    {
        return Upgrade::create([
            'service_id' => $this->service->id,
            'old_product_id' => $this->service->product_id,
            'new_product_id' => $newProduct->id,
            'customer_id' => $this->service->customer_id,
        ]);
    }

    public function getUpgradeName(Product $newProduct)
    {
        if ($this->service->billing == 'onetime') {
            return __('client.services.upgrade.upgrade_to2', ['product' => $newProduct->name]);
        }
        $current = Carbon::now();
        $expiresAt = $expiresAt ?? $this->service->expires_at;

        return __('client.services.upgrade.upgrade_to2', ['product' => $newProduct->name])." ({$current->format('d/m/y')} - {$expiresAt->format('d/m/y')})";
    }

    public function getUpgradeDescription(Product $newProduct)
    {
        return '';
    }

    public function toInvoiceItem(Product $newProduct, Upgrade $upgrade)
    {
        $price = $this->generatePrice($newProduct);
        return [
            'name' => $this->getUpgradeName($newProduct),
            'description' => $this->getUpgradeDescription($newProduct),
            'quantity' => 1,
            'unit_price_ttc' => $price->firstPaymentTTC(),
            'unit_price_ht' => $price->firstPaymentHT(),
            'unit_setup_ttc' => $price->setupTTC(),
            'unit_setup_ht' => $price->setupHT(),
            'type' => 'upgrade',
            'related_id' => $upgrade->id,
            'data' => [],
        ];
    }
}
