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
namespace App\Models\Traits;

use App\Abstracts\PaymentMethodSourceDTO;
use App\Services\Store\GatewayService;
use Illuminate\Support\Facades\Cache;

trait HasPaymentMethods
{
    public function getDefaultPaymentMethod()
    {
        if ($this->hasMetadata('default_payment_method')) {
            return $this->getMetadata('default_payment_method');
        }

        return null;
    }

    public function setDefaultPaymentMethod($paymentMethod)
    {
        $this->attachMetadata('default_payment_method', $paymentMethod);
    }

    public function paymentMethods()
    {
        return Cache::rememberForever('payment_methods_'.$this->id, function () {
            /** @var \App\Models\Billing\Gateway[] $gateways */
            $gateways = GatewayService::getAvailable(-1);

            return collect($gateways)->map(function ($gateway) {
                return $gateway->paymentType()->getSources($this);
            })->flatten();
        });
    }

    public function getPaymentMethodsArray(bool $idOnly = false): \Illuminate\Support\Collection
    {
        $paymentmethods = $this->paymentMethods();
        if ($paymentmethods->isNotEmpty()) {
            $paymentmethods = collect(['default' => __('client.payment-methods.default')])->merge($paymentmethods->mapWithKeys(function (PaymentMethodSourceDTO $sourceDTO) use ($idOnly) {
                return [$sourceDTO->id => $idOnly ? $sourceDTO->id : $sourceDTO->title()];
            }));
        }

        return $paymentmethods;
    }
}
