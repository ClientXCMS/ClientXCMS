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
namespace App\Services\Store;

use App\Models\Billing\Gateway;
use Illuminate\Support\Facades\Cache;

class GatewayService
{
    /**
     * Get available gateways
     * 0 to get only balance gateways
     * Any other number to get all gateways with amount
     *
     * @return array|void
     */
    public static function getAvailable(float $amount = 0)
    {
        if (! is_installed()) {
            return [];
        }
        $gateways = Cache::remember('gateways', 60 * 60 * 24, function () {
            return Gateway::getAvailable()->get();
        });
        if ($amount == 0) {
            return $gateways->filter(function ($gateway) {
                return $gateway->uuid == 'balance';
            });
        }

        return $gateways;
    }

    public static function forgotAvailable()
    {
        Cache::forget('gateways');
    }
}
