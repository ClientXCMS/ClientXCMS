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

namespace App\Abstracts;

use App\Models\Account\Customer;

class PaymentMethodSourceDTO
{
    public string $id;

    public string $brand;

    public string $exp_month;

    public string $last4;

    public string $exp_year;

    public int $customerId;

    public string $gateway_uuid;

    public ?string $email;

    public function __construct(string $id, string $brand, string $last4, string $exp_month, string $exp_year, int $customerId, string $gateway_uuid, ?string $email = null)
    {
        $this->id = $id;
        $this->brand = $brand;
        $this->last4 = $last4;
        $this->exp_month = $exp_month;
        $this->exp_year = $exp_year;
        $this->customerId = $customerId;
        $this->gateway_uuid = $gateway_uuid;
        $this->email = $email;
    }

    public function isDefault(?Customer $customer = null): bool
    {
        $customer = $customer ?? \Auth::user();
        if (! $customer instanceof Customer) {
            return false;
        }

        return $customer->getDefaultPaymentMethod() === $this->id;
    }

    public function title()
    {
        if ($this->gateway_uuid == 'paypal_express_checkout'){
            return "PayPal (".$this->email.")" . ($this->isDefault() ? ' - '.__('client.payment-methods.default') : '');
        }
        return $this->brand.' **** **** **** '.$this->last4.' ('.$this->exp_month.'/'.$this->exp_year.')'.($this->isDefault() ? ' - '.__('client.payment-methods.default') : '');
    }
}
