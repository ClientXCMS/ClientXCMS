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


namespace App\Services\Core;

use App\Core\Gateway\BalanceType;
use App\Core\Gateway\BankTransfertType;
use App\Core\Gateway\NoneGatewayType;
use App\Core\Gateway\PayPalExpressCheckoutType;
use App\Core\Gateway\PayPalMethodType;
use App\Core\Gateway\StancerType;
use App\Core\Gateway\StripeType;
use Illuminate\Support\Collection;

class PaymentTypeService
{
    private Collection $paymentMethods;

    public function __construct()
    {
        $this->paymentMethods = collect([
            PayPalMethodType::UUID => PayPalMethodType::class,
            PayPalExpressCheckoutType::UUID => PayPalExpressCheckoutType::class,
            StripeType::UUID => StripeType::class,
            BalanceType::UUID => BalanceType::class,
            BankTransfertType::UUID => BankTransfertType::class,
            NoneGatewayType::UUID => NoneGatewayType::class,
            StancerType::UUID => StancerType::class,
        ]);
    }

    public function all()
    {
        return $this->paymentMethods;
    }

    public function get(string $uuid)
    {
        return app($this->paymentMethods->get($uuid));
    }

    public function add(string $uuid, string $class)
    {
        $this->paymentMethods = $this->paymentMethods->merge([$uuid => $class]);
    }
}
