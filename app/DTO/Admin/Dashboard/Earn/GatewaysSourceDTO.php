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
namespace App\DTO\Admin\Dashboard\Earn;

use App\Models\Billing\Gateway;
use Illuminate\Support\Collection;

class GatewaysSourceDTO
{
    public Collection $items;

    public array $names = [];

    public array $icons = [];

    public array $amounts = [];

    public array $counts = [];

    public array $percentages = [];

    public function __construct(Collection $data)
    {
        $this->items = $data;
        collect($this->items)->map(function ($item) {
            $gateway = Gateway::where('uuid', $item['paymethod'])->first();
            $count = $this->items->sum('count');
            $percent = $item['count'] / $count * 100;
            $percent = number_format($percent);
            $this->names[$item['paymethod']] = $gateway ? $gateway->name : $item['paymethod'];
            $this->icons[$item['paymethod']] = $gateway ? $gateway->paymentType()->icon() : 'bi bi-credit-card';
            $this->amounts[$item['paymethod']] = $item['subtotal'];
            $this->counts[$item['paymethod']] = $item['count'];
            $this->percentages[$item['paymethod']] = $percent;
        });
    }

    public function isEmpty()
    {
        return count($this->items) == 0;
    }
}
